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
}

func TestService_Generate_Success(t *testing.T) {
	mock := &testutil.MockLLMClient{Response: testutil.SampleLLMResponse()}
	svc := recipe.NewService(mock)

	res, metadata, err := svc.Generate(context.Background(), sampleInput)

	require.NoError(t, err)
	assert.True(t, res.IsRecipe)
	assert.Equal(t, "テスト料理", res.Title)
	assert.Equal(t, "gemini-2.5-flash", metadata.ModelVersion)
	assert.Equal(t, 300, metadata.UsageMetadata.TotalTokenCount)
}

func TestService_Generate_NotRecipe(t *testing.T) {
	notRecipeResult := testutil.SampleLLMResponse()
	notRecipeResult.Metadata.ModelVersion = "gemini-2.5-flash"
	notRecipeResult.Metadata.UsageMetadata.TotalTokenCount = 300
	notRecipeResult.RawText = `{"is_recipe": false}`
	mock := &testutil.MockLLMClient{Response: notRecipeResult}
	svc := recipe.NewService(mock)

	res, metadata, err := svc.Generate(context.Background(), sampleInput)

	assert.Nil(t, res)
	assert.Nil(t, metadata)
	assert.ErrorIs(t, err, domain.ErrNotRecipeError)
}

func TestService_Generate_LLMError(t *testing.T) {
	mock := &testutil.MockLLMClient{Err: domain.ErrGenerationFailed}
	svc := recipe.NewService(mock)

	res, metadata, err := svc.Generate(context.Background(), sampleInput)

	assert.Nil(t, res)
	assert.Nil(t, metadata)
	assert.ErrorIs(t, err, domain.ErrGenerationFailed)
}

func TestService_Generate_RateLimitError(t *testing.T) {
	mock := &testutil.MockLLMClient{Err: domain.ErrResourceExhausted}
	svc := recipe.NewService(mock)

	_, _, err := svc.Generate(context.Background(), sampleInput)

	assert.ErrorIs(t, err, domain.ErrResourceExhausted)
}
