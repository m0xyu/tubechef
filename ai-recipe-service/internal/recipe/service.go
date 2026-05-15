package recipe

import (
	"context"
	"encoding/json"
	"fmt"
	"log/slog"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
)

// Service はレシピ生成のビジネスロジックを担う
type Service struct {
	llm domain.LLMClient
}

func NewService(llm domain.LLMClient) *Service {
	return &Service{llm: llm}
}

// Generate は動画メタデータからレシピを生成して返す
// 料理動画でない場合は domain.ErrNotRecipeError を返す
func (s *Service) Generate(ctx context.Context, input domain.VideoInput) (*domain.GeneratedRecipe, *domain.LLMMetadata, error) {
	prompt := buildPrompt(input)
	schema := buildResponseSchema()
	budget := defaultThinkingBudget

	result, err := s.llm.GenerateContent(ctx, domain.LLMRequest{
		SystemInstruction: systemInstruction,
		Prompt:            prompt,
		MediaURIs:         []string{"https://www.youtube.com/watch?v=" + input.VideoID},
		Config: &domain.LLMConfig{
			ResponseFormat: "application/json",
			ResponseSchema: schema,
			ThinkingBudget: &budget,
		},
	})

	if err != nil {
		return nil, nil, fmt.Errorf("llm.GenerateContent: %w", err)
	}

	var recipe domain.GeneratedRecipe
	if err := json.Unmarshal([]byte(result.RawText), &recipe); err != nil {
		slog.Error("Gemini JSON parse error", "raw_text", result.RawText, "error", err)
		return nil, nil, fmt.Errorf("%w: %w", domain.ErrGenerationFailed, err)
	}

	slog.Info("recipe generated",
		"video_id", input.VideoID,
		"model", result.Metadata.ModelVersion,
		"total_tokens", result.Metadata.UsageMetadata.TotalTokenCount,
		"is_recipe", recipe.IsRecipe,
	)

	if !recipe.IsRecipe {
		return nil, nil, domain.ErrNotRecipeError
	}

	return &recipe, result.Metadata, nil
}
