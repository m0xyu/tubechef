package recipe

import (
	"context"
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
func (s *Service) Generate(ctx context.Context, input domain.VideoInput) (*domain.LLMResult, error) {
	result, err := s.llm.GenerateRecipe(ctx, input)
	if err != nil {
		return nil, fmt.Errorf("llm.GenerateRecipe: %w", err)
	}

	slog.Info("recipe generated",
		"video_id", input.VideoID,
		"model", result.Metadata.ModelVersion,
		"total_tokens", result.Metadata.UsageMetadata.TotalTokenCount,
		"is_recipe", result.Recipe.IsRecipe,
	)

	if !result.Recipe.IsRecipe {
		return nil, domain.ErrNotRecipeError
	}

	return result, nil
}
