package gemini

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"log/slog"
	"net/http"
	"time"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
	"github.com/m0xyu/tubechef/ai-recipe-service/internal/config"
)

const requestTimeout = 180 * time.Second

// Client は domain.LLMClient を実装する
type Client struct {
	httpClient *http.Client
	baseURL    string
	apiKey     string
	model      string
}

func NewClient(cfg *config.Config) *Client {
	return &Client{
		httpClient: &http.Client{Timeout: requestTimeout},
		baseURL:    cfg.GeminiBaseURL,
		apiKey:     cfg.GeminiAPIKey,
		model:      cfg.GeminiModel,
	}
}

// GenerateRecipe は domain.LLMClient インターフェースを実装する
func (c *Client) GenerateRecipe(ctx context.Context, input domain.VideoInput) (*domain.LLMResult, error) {
	payload, err := c.buildPayload(input)
	if err != nil {
		return nil, fmt.Errorf("build payload: %w", err)
	}

	raw, err := c.post(ctx, payload)
	if err != nil {
		return nil, err
	}

	return parseResponse(raw)
}

// --- リクエスト構築 ---

type requestPayload struct {
	Contents          []content          `json:"contents"`
	SystemInstruction systemInstructionP `json:"system_instruction"`
	GenerationConfig  generationConfig   `json:"generationConfig"`
}

type content struct {
	Parts []part `json:"parts"`
}

type part struct {
	Text     string    `json:"text,omitempty"`
	FileData *fileData `json:"file_data,omitempty"`
}

type fileData struct {
	FileURI string `json:"file_uri"`
}

type systemInstructionP struct {
	Parts []part `json:"parts"`
}

type generationConfig struct {
	ResponseMIMEType string         `json:"responseMimeType"`
	ResponseSchema   responseSchema `json:"responseSchema"`
}

func (c *Client) buildPayload(input domain.VideoInput) ([]byte, error) {
	videoURL := fmt.Sprintf("https://www.youtube.com/watch?v=%s", input.VideoID)

	payload := requestPayload{
		Contents: []content{
			{
				Parts: []part{
					{Text: buildPrompt(input)},
					{FileData: &fileData{FileURI: videoURL}},
				},
			},
		},
		SystemInstruction: systemInstructionP{
			Parts: []part{{Text: systemInstruction}},
		},
		GenerationConfig: generationConfig{
			ResponseMIMEType: "application/json",
			ResponseSchema:   buildResponseSchema(),
		},
	}

	return json.Marshal(payload)
}

// --- HTTP送信 ---

func (c *Client) post(ctx context.Context, body []byte) ([]byte, error) {
	url := fmt.Sprintf("%s/v1beta/models/%s:generateContent?key=%s", c.baseURL, c.model, c.apiKey)

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, url, bytes.NewReader(body))
	if err != nil {
		return nil, fmt.Errorf("create request: %w", err)
	}
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("%w: %w", domain.ErrUnavailable, err)
	}
	defer resp.Body.Close()

	raw, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("read response body: %w", err)
	}

	if resp.StatusCode != http.StatusOK {
		return nil, mapHTTPError(resp.StatusCode, raw)
	}

	return raw, nil
}

func mapHTTPError(status int, body []byte) error {
	slog.Error("Gemini API error", "status", status, "body", string(body))
	switch status {
	case http.StatusBadRequest:
		return domain.ErrInvalidArgument
	case http.StatusForbidden:
		return domain.ErrPermissionDenied
	case http.StatusNotFound:
		return domain.ErrNotFound
	case http.StatusTooManyRequests:
		return domain.ErrResourceExhausted
	case http.StatusServiceUnavailable:
		return domain.ErrUnavailable
	case http.StatusGatewayTimeout:
		return domain.ErrDeadlineExceeded
	default:
		return domain.ErrInternalServerError
	}
}

// --- レスポンスパース ---

type apiResponse struct {
	Candidates []struct {
		Content struct {
			Parts []struct {
				Text string `json:"text"`
			} `json:"parts"`
		} `json:"content"`
		FinishReason  string `json:"finishReason"`
		FinishMessage string `json:"finishMessage"`
	} `json:"candidates"`
	UsageMetadata struct {
		PromptTokenCount           int                  `json:"promptTokenCount"`
		CachedContentTokenCount    int                  `json:"cachedContentTokenCount"`
		CandidatesTokenCount       int                  `json:"candidatesTokenCount"`
		ToolUsePromptTokenCount    int                  `json:"toolUsePromptTokenCount"`
		ThoughtsTokenCount         int                  `json:"thoughtsTokenCount"`
		TotalTokenCount            int                  `json:"totalTokenCount"`
		PromptTokensDetails        []modalityTokenCount `json:"promptTokensDetails"`
		CacheTokensDetails         []modalityTokenCount `json:"cacheTokensDetails"`
		CandidatesTokensDetails    []modalityTokenCount `json:"candidatesTokensDetails"`
		ToolUsePromptTokensDetails []modalityTokenCount `json:"toolUsePromptTokensDetails"`
	} `json:"usageMetadata"`
	ModelVersion string `json:"modelVersion"`
}

type modalityTokenCount struct {
	Modality   string `json:"modality"`
	TokenCount int    `json:"tokenCount"`
}

func toModalityTokenCounts(src []modalityTokenCount) []domain.ModalityTokenCount {
	result := make([]domain.ModalityTokenCount, len(src))
	for i, m := range src {
		result[i] = domain.ModalityTokenCount{
			Modality:   m.Modality,
			TokenCount: m.TokenCount,
		}
	}
	return result
}

func parseResponse(raw []byte) (*domain.LLMResult, error) {
	var apiResp apiResponse
	if err := json.Unmarshal(raw, &apiResp); err != nil {
		return nil, fmt.Errorf("unmarshal api response: %w", err)
	}

	if len(apiResp.Candidates) == 0 {
		return nil, fmt.Errorf("%w: no candidates in response", domain.ErrGenerationFailed)
	}

	candidate := apiResp.Candidates[0]
	u := apiResp.UsageMetadata

	metadata := domain.LLMMetadata{
		ModelVersion:  apiResp.ModelVersion,
		FinishReason:  candidate.FinishReason,
		FinishMessage: candidate.FinishMessage,
		UsageMetadata: domain.UsageMetadata{
			PromptTokenCount:           u.PromptTokenCount,
			CachedContentTokenCount:    u.CachedContentTokenCount,
			CandidatesTokenCount:       u.CandidatesTokenCount,
			ToolUsePromptTokenCount:    u.ToolUsePromptTokenCount,
			ThoughtsTokenCount:         u.ThoughtsTokenCount,
			TotalTokenCount:            u.TotalTokenCount,
			PromptTokensDetails:        toModalityTokenCounts(u.PromptTokensDetails),
			CacheTokensDetails:         toModalityTokenCounts(u.CacheTokensDetails),
			CandidatesTokensDetails:    toModalityTokenCounts(u.CandidatesTokensDetails),
			ToolUsePromptTokensDetails: toModalityTokenCounts(u.ToolUsePromptTokensDetails),
		},
	}

	if candidate.FinishReason != "STOP" {
		slog.Warn("Gemini generation stopped unexpectedly",
			"finish_reason", candidate.FinishReason,
			"finish_message", candidate.FinishMessage,
			"model", apiResp.ModelVersion,
		)
		return nil, fmt.Errorf("%w: finish_reason=%s", domain.ErrGenerationFailed, candidate.FinishReason)
	}

	if len(candidate.Content.Parts) == 0 {
		return nil, fmt.Errorf("%w: empty content parts", domain.ErrGenerationFailed)
	}

	text := candidate.Content.Parts[0].Text

	var recipe domain.GeneratedRecipe
	if err := json.Unmarshal([]byte(text), &recipe); err != nil {
		slog.Error("Gemini JSON parse error", "raw_text", text, "error", err)
		return nil, fmt.Errorf("%w: %w", domain.ErrGenerationFailed, err)
	}

	return &domain.LLMResult{
		Recipe:   &recipe,
		Metadata: metadata,
	}, nil
}
