package gemini

import (
	"encoding/json"
	"fmt"
	"log/slog"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
)

type apiResponse struct {
	Candidates    []apiCandidate   `json:"candidates"`
	UsageMetadata apiUsageMetadata `json:"usageMetadata"`
	ModelVersion  string           `json:"modelVersion"`
}

type apiCandidate struct {
	Content struct {
		Parts []struct {
			Text string `json:"text"`
		} `json:"parts"`
	} `json:"content"`
	FinishReason  string `json:"finishReason"`
	FinishMessage string `json:"finishMessage"`
}

type apiUsageMetadata struct {
	PromptTokenCount           int                     `json:"promptTokenCount"`
	CachedContentTokenCount    int                     `json:"cachedContentTokenCount"`
	CandidatesTokenCount       int                     `json:"candidatesTokenCount"`
	ToolUsePromptTokenCount    int                     `json:"toolUsePromptTokenCount"`
	ThoughtsTokenCount         int                     `json:"thoughtsTokenCount"`
	TotalTokenCount            int                     `json:"totalTokenCount"`
	PromptTokensDetails        []apiModalityTokenCount `json:"promptTokensDetails"`
	CacheTokensDetails         []apiModalityTokenCount `json:"cacheTokensDetails"`
	CandidatesTokensDetails    []apiModalityTokenCount `json:"candidatesTokensDetails"`
	ToolUsePromptTokensDetails []apiModalityTokenCount `json:"toolUsePromptTokensDetails"`
}

func (u *apiUsageMetadata) toDomain() domain.UsageMetadata {
	return domain.UsageMetadata{
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
	}
}

type apiModalityTokenCount struct {
	Modality   string `json:"modality"`
	TokenCount int    `json:"tokenCount"`
}

func toModalityTokenCounts(src []apiModalityTokenCount) []domain.ModalityTokenCount {
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
		Recipe: &recipe,
		Metadata: domain.LLMMetadata{
			ModelVersion:  apiResp.ModelVersion,
			FinishReason:  candidate.FinishReason,
			FinishMessage: candidate.FinishMessage,
			UsageMetadata: apiResp.UsageMetadata.toDomain(),
		},
	}, nil
}
