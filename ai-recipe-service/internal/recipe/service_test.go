package recipe_test

import (
	"context"
	"testing"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
	"github.com/m0xyu/tubechef/ai-recipe-service/internal/recipe"
	"github.com/m0xyu/tubechef/ai-recipe-service/internal/testutil"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

var sampleInput = domain.VideoInput{
	VideoID:     "abc123",
	Title:       "簡単！鶏の唐揚げ",
	Description: "鶏もも肉を使った...",
	DurationSec: 600,
}

func TestService_Generate_Success(t *testing.T) {
	mock := &testutil.MockLLMClient{Result: testutil.SampleLLMResult()}
	svc := recipe.NewService(mock)

	result, err := svc.Generate(context.Background(), sampleInput)

	require.NoError(t, err)
	assert.True(t, result.Recipe.IsRecipe)
	assert.Equal(t, "テスト料理", result.Recipe.Title)
	assert.Equal(t, "gemini-2.0-flash-001", result.Metadata.ModelVersion)
	assert.Equal(t, 300, result.Metadata.UsageMetadata.TotalTokenCount)
}

func TestService_Generate_NotRecipe(t *testing.T) {
	notRecipeResult := testutil.SampleLLMResult()
	notRecipeResult.Recipe.IsRecipe = false
	mock := &testutil.MockLLMClient{Result: notRecipeResult}
	svc := recipe.NewService(mock)

	result, err := svc.Generate(context.Background(), sampleInput)

	assert.Nil(t, result)
	assert.ErrorIs(t, err, domain.ErrNotRecipeError)
}

func TestService_Generate_LLMError(t *testing.T) {
	mock := &testutil.MockLLMClient{Err: domain.ErrGenerationFailed}
	svc := recipe.NewService(mock)

	result, err := svc.Generate(context.Background(), sampleInput)

	assert.Nil(t, result)
	assert.ErrorIs(t, err, domain.ErrGenerationFailed)
}

func TestService_Generate_RateLimitError(t *testing.T) {
	mock := &testutil.MockLLMClient{Err: domain.ErrResourceExhausted}
	svc := recipe.NewService(mock)

	_, err := svc.Generate(context.Background(), sampleInput)

	assert.ErrorIs(t, err, domain.ErrResourceExhausted)
}
