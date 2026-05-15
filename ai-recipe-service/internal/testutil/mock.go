package testutil

import (
	"context"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
)

// MockLLMClient は domain.LLMClient のテスト用モック
type MockLLMClient struct {
	Response *domain.LLMResponse
	Err      error
}

func (m *MockLLMClient) GenerateContent(_ context.Context, _ domain.LLMRequest) (*domain.LLMResponse, error) {
	return m.Response, m.Err
}

// MockRecipeGenerator は handler.RecipeGenerator のテスト用モック
type MockRecipeGenerator struct {
	Recipe   *domain.GeneratedRecipe
	Metadata *domain.LLMMetadata
	Err      error
}

func (m *MockRecipeGenerator) Generate(_ context.Context, _ domain.VideoInput) (*domain.GeneratedRecipe, *domain.LLMMetadata, error) {
	return m.Recipe, m.Metadata, m.Err
}

// SampleGeneratedRecipe はテストで使う正常系のレスポンス
func SampleGeneratedRecipe() *domain.GeneratedRecipe {
	servingSize := "2人前"
	cookingTime := 30
	qty := "200g"
	group := "具材"
	start := 10
	end := 60
	relatedStep := 1
	tipStart := 15

	return &domain.GeneratedRecipe{
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
	}
}

func SampleLLMMetadata() *domain.LLMMetadata {
	return &domain.LLMMetadata{
		ModelVersion: "gemini-2.5-flash",
		FinishReason: "stop",
		UsageMetadata: domain.UsageMetadata{
			PromptTokenCount:     100,
			CandidatesTokenCount: 200,
			TotalTokenCount:      300,
		},
	}
}

func SampleLLMResponse() *domain.LLMResponse {
	rawJSON := `{
		"is_recipe": true,
		"title": "テスト料理",
		"summary": "テスト用のサマリー",
		"dish_name": "テスト丼",
		"dish_slug": "test-don",
		"serving_size": "2人前",
		"cooking_time": 30,
		"ingredients": [
			{"name": "鶏肉", "quantity": "200g", "group": "具材", "order": 1}
		],
		"steps": [
			{"step_number": 1, "description": "鶏肉を切る", "start_time_in_seconds": 10, "end_time_in_seconds": 60}
		],
		"tips": [
			{"description": "冷たい鶏肉を使うと切りやすい", "related_step_number": 1, "start_time_in_seconds": 15}
		]
	}`

	return &domain.LLMResponse{
		RawText:  rawJSON,
		Metadata: SampleLLMMetadata(),
	}
}
