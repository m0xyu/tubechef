package gemini

import (
	"fmt"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
)

const systemInstruction = `あなたはプロの料理研究家兼データエンジニアです。`

func buildPrompt(input domain.VideoInput) string {
	return fmt.Sprintf(`提供される「YouTube動画（映像・音声）」および「タイトル・概要欄」を総合的に分析し、正確なレシピデータを抽出してください。
概要欄に分量や手順が記載されていない場合は、動画内の映像や音声解説から情報を補完してください。
料理動画ではない場合（ゲーム実況やニュースなど）は、is_recipeをfalseにしてください。

## 動画タイトル
%s

## 概要欄
%s`, input.Title, input.Description)
}

// responseSchema は Gemini の structured output 用 JSON Schema
// https://ai.google.dev/api/generate-content#v1beta.Schema
type responseSchema struct {
	Type       string              `json:"type"`
	Properties map[string]property `json:"properties"`
	Required   []string            `json:"required"`
}

type property struct {
	Type        string              `json:"type"`
	Description string              `json:"description,omitempty"`
	Nullable    bool                `json:"nullable,omitempty"`
	Items       *itemSchema         `json:"items,omitempty"`
}

type itemSchema struct {
	Type       string              `json:"type"`
	Properties map[string]property `json:"properties,omitempty"`
	Required   []string            `json:"required,omitempty"`
}

func buildResponseSchema() responseSchema {
	return responseSchema{
		Type: "object",
		Properties: map[string]property{
			"is_recipe": {
				Type:        "boolean",
				Description: "動画の内容が料理レシピであるかどうか",
			},
			"title": {
				Type:        "string",
				Description: "料理名。動画タイトルから抽出",
			},
			"summary": {
				Type:        "string",
				Description: "レシピの魅力や要約（100文字程度）",
			},
			"dish_name": {
				Type:        "string",
				Description: "料理の名前だけ。シンプルで一般的な名前にして。",
			},
			"dish_slug": {
				Type:        "string",
				Description: "料理名のスラッグ（英数字とハイフンのみ）",
			},
			"serving_size": {
				Type:        "string",
				Nullable:    true,
				Description: "分量（例: 2人前）。不明な場合はnull",
			},
			"cooking_time": {
				Type:        "integer",
				Nullable:    true,
				Description: "調理時間（分単位の数値）。不明な場合はnull",
			},
			"ingredients": {
				Type: "array",
				Items: &itemSchema{
					Type: "object",
					Properties: map[string]property{
						"name":     {Type: "string", Description: "材料名"},
						"quantity": {Type: "string", Nullable: true, Description: "分量"},
						"group":    {Type: "string", Nullable: true, Description: "材料のグループ（例: 具材, 調味料, トッピング）。分類不可ならnull"},
						"order":    {Type: "integer", Description: "表示順"},
					},
					Required: []string{"name", "order"},
				},
			},
			"steps": {
				Type: "array",
				Items: &itemSchema{
					Type: "object",
					Properties: map[string]property{
						"step_number":           {Type: "integer"},
						"description":           {Type: "string", Description: "手順の説明"},
						"start_time_in_seconds": {Type: "integer", Description: "手順の開始時間（秒）"},
						"end_time_in_seconds":   {Type: "integer", Nullable: true, Description: "手順の終了時間（秒）。不明な場合はnull"},
					},
					Required: []string{"step_number", "description", "start_time_in_seconds"},
				},
			},
			"tips": {
				Type: "array",
				Items: &itemSchema{
					Type: "object",
					Properties: map[string]property{
						"description":          {Type: "string", Description: "特に大事なコツやポイントを最大5つまで"},
						"related_step_number":  {Type: "integer", Nullable: true},
						"start_time_in_seconds": {Type: "integer", Nullable: true, Description: "コツが紹介される開始時間。不明な場合はnull"},
					},
					Required: []string{"description"},
				},
			},
		},
		Required: []string{"is_recipe", "title", "ingredients", "steps", "dish_name", "dish_slug"},
	}
}
