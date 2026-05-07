package testutil

import (
	"context"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
)

// MockLLMClient は domain.LLMClient のテスト用モック
type MockLLMClient struct {
	Result *domain.LLMResult
	Err    error
}

func (m *MockLLMClient) GenerateRecipe(_ context.Context, _ domain.VideoInput) (*domain.LLMResult, error) {
	return m.Result, m.Err
}

// MockRecipeGenerator は handler.RecipeGenerator のテスト用モック
type MockRecipeGenerator struct {
	Result *domain.LLMResult
	Err    error
}

func (m *MockRecipeGenerator) Generate(_ context.Context, _ domain.VideoInput) (*domain.LLMResult, error) {
	return m.Result, m.Err
}

// SampleLLMResult はテストで使う正常系のレスポンス
func SampleLLMResult() *domain.LLMResult {
	servingSize := "2人前"
	cookingTime := 30
	qty := "200g"
	group := "具材"
	start := 10
	end := 60
	relatedStep := 1
	tipStart := 15

	return &domain.LLMResult{
		Recipe: &domain.GeneratedRecipe{
			IsRecipe:    true,
			Title:       "テスト料理",
			Summary:     "テスト用のサマリー",
			DishName:    "テスト丼",
			DishSlug:    "test-don",
			ServingSize: &servingSize,
			CookingTime: &cookingTime,
			Ingredients: []domain.Ingredient{
				{Name: "鶏肉", Quantity: &qty, Group: &group, Order: 1},
			},
			Steps: []domain.RecipeStep{
				{StepNumber: 1, Description: "鶏肉を切る", StartTimeInSeconds: &start, EndTimeInSeconds: &end},
			},
			Tips: []domain.RecipeTip{
				{Description: "冷たい鶏肉を使うと切りやすい", RelatedStepNumber: &relatedStep, StartTimeInSeconds: &tipStart},
			},
		},
		Metadata: domain.LLMMetadata{
			ModelVersion: "gemini-2.0-flash-001",
			FinishReason: "STOP",
			UsageMetadata: domain.UsageMetadata{
				PromptTokenCount:     100,
				CandidatesTokenCount: 200,
				TotalTokenCount:      300,
			},
		},
	}
}
