package handler

import (
	"context"
	"encoding/json"
	"net/http"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
	"github.com/m0xyu/tubechef/ai-recipe-service/internal/utils"
)

// RecipeGenerator はレシピ生成サービスの抽象（テスト時にモック可能）
type RecipeGenerator interface {
	Generate(ctx context.Context, input domain.VideoInput) (*domain.LLMResult, error)
}

type GenerateHandler struct {
	service RecipeGenerator
}

func NewGenerateHandler(service RecipeGenerator) *GenerateHandler {
	return &GenerateHandler{service: service}
}

// generateRequest は POST /generate のリクエストボディ
type generateRequest struct {
	VideoID     string `json:"video_id"`
	Title       string `json:"title"`
	Description string `json:"description"`
	DurationSec int    `json:"duration_sec"`
}

func (req *generateRequest) validate() string {
	if req.VideoID == "" {
		return "video_id is required"
	}
	if req.Title == "" {
		return "title is required"
	}
	return ""
}

// generateResponse は POST /generate のレスポンスボディ
type generateResponse struct {
	Recipe   *domain.GeneratedRecipe `json:"recipe"`
	Metadata metadataResponse        `json:"metadata"`
}

type metadataResponse struct {
	ModelVersion string                  `json:"model_version"`
	FinishReason string                  `json:"finish_reason"`
	Usage        domain.UsageMetadata    `json:"usage"`
}

func (h *GenerateHandler) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	var req generateRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		utils.BadRequestResponse(w, r, "invalid JSON body")
		return
	}

	if msg := req.validate(); msg != "" {
		utils.BadRequestResponse(w, r, msg)
		return
	}

	input := domain.VideoInput{
		VideoID:     req.VideoID,
		Title:       req.Title,
		Description: req.Description,
		DurationSec: req.DurationSec,
	}

	result, err := h.service.Generate(r.Context(), input)
	if err != nil {
		utils.ErrorResponse(w, r, err)
		return
	}

	utils.CreatedResponse(w, r, "レシピを生成しました", generateResponse{
		Recipe: result.Recipe,
		Metadata: metadataResponse{
			ModelVersion: result.Metadata.ModelVersion,
			FinishReason: result.Metadata.FinishReason,
			Usage:        result.Metadata.UsageMetadata,
		},
	})
}
