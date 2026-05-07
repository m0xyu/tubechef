package gemini

import (
	"strings"
	"testing"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
	"github.com/stretchr/testify/assert"
)

func TestBuildPrompt(t *testing.T) {
	input := domain.VideoInput{
		VideoID:     "abc123",
		Title:       "簡単！鶏の唐揚げ",
		Description: "材料：鶏もも肉 500g...",
	}

	got := buildPrompt(input)

	assert.Contains(t, got, input.Title)
	assert.Contains(t, got, input.Description)
}

func TestBuildPrompt_EmptyDescription(t *testing.T) {
	input := domain.VideoInput{
		VideoID: "abc123",
		Title:   "鶏の唐揚げ",
	}

	got := buildPrompt(input)

	assert.Contains(t, got, input.Title)
	assert.True(t, strings.Contains(got, "## 概要欄"))
}

func TestBuildResponseSchema(t *testing.T) {
	schema := buildResponseSchema()

	assert.Equal(t, "object", schema.Type)

	requiredFields := []string{"is_recipe", "title", "ingredients", "steps", "dish_name", "dish_slug"}
	for _, field := range requiredFields {
		assert.Contains(t, schema.Required, field, "required field missing: %s", field)
	}

	// ingredients の items 定義を確認
	ingredients, ok := schema.Properties["ingredients"]
	assert.True(t, ok)
	assert.NotNil(t, ingredients.Items)
	assert.Contains(t, ingredients.Items.Required, "name")
	assert.Contains(t, ingredients.Items.Required, "order")

	// steps の items 定義を確認
	steps, ok := schema.Properties["steps"]
	assert.True(t, ok)
	assert.NotNil(t, steps.Items)
	assert.Contains(t, steps.Items.Required, "step_number")
	assert.Contains(t, steps.Items.Required, "description")
}
