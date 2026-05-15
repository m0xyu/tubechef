package gemini

import (
	"context"
	"fmt"
	"net/http"
	"time"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
	"github.com/m0xyu/tubechef/ai-recipe-service/internal/config"
)

// Client は domain.LLMClient を実装する
type Client struct {
	httpClient *http.Client
	baseURL    string
	apiKey     string
	model      string
}

func NewClient(cfg *config.Config) *Client {
	timeout := time.Duration(cfg.GeminiRequestTimeoutSec) * time.Second
	return &Client{
		httpClient: &http.Client{Timeout: timeout},
		baseURL:    cfg.GeminiBaseURL,
		apiKey:     cfg.GeminiAPIKey,
		model:      cfg.GeminiModel,
	}
}

// GenerateContent は domain.LLMClient インターフェースを実装する
func (c *Client) GenerateContent(ctx context.Context, req domain.LLMRequest) (*domain.LLMResponse, error) {
	payload, err := c.buildPayload(req)
	if err != nil {
		return nil, fmt.Errorf("build payload: %w", err)
	}

	raw, err := c.post(ctx, payload)
	if err != nil {
		return nil, err
	}

	return parseResponse(raw)
}
