package gemini

import (
	"context"
	"fmt"
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
