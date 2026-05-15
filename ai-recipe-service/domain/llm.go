package domain

import "context"

// LLMClient はLLMプロバイダーの抽象（実装はinternalに置く）
type LLMClient interface {
	GenerateContent(ctx context.Context, req LLMRequest) (*LLMResponse, error)
}

type LLMConfig struct {
	ResponseFormat string
	ResponseSchema any
	ThinkingBudget *int
}

type LLMRequest struct {
	SystemInstruction string
	Prompt            string
	MediaURIs         []string
	Config            *LLMConfig
}

type LLMResponse struct {
	RawText  string
	Metadata *LLMMetadata
}

// LLMMetadata は Gemini の usageMetadata + modelVersion に対応
// https://ai.google.dev/api/generate-content#v1beta.UsageMetadata
type LLMMetadata struct {
	ModelVersion  string
	FinishReason  string
	FinishMessage string
	UsageMetadata UsageMetadata
}

type UsageMetadata struct {
	PromptTokenCount           int                  `json:"prompt_token_count"`
	CachedContentTokenCount    int                  `json:"cached_content_token_count"`
	CandidatesTokenCount       int                  `json:"candidates_token_count"`
	ToolUsePromptTokenCount    int                  `json:"tool_use_prompt_token_count"`
	ThoughtsTokenCount         int                  `json:"thoughts_token_count"`
	TotalTokenCount            int                  `json:"total_token_count"`
	PromptTokensDetails        []ModalityTokenCount `json:"prompt_tokens_details"`
	CacheTokensDetails         []ModalityTokenCount `json:"cache_tokens_details"`
	CandidatesTokensDetails    []ModalityTokenCount `json:"candidates_tokens_details"`
	ToolUsePromptTokensDetails []ModalityTokenCount `json:"tool_use_prompt_tokens_details"`
}

// ModalityTokenCount はモダリティ（テキスト・画像・音声など）ごとのトークン内訳
type ModalityTokenCount struct {
	Modality   string `json:"modality"`
	TokenCount int    `json:"token_count"`
}
