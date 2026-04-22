package domain

type LLMResponse struct {
	Data       *GenerateRecipeResponse
	Model      string
	Metadata   LLMMetadata
	RawContent string
}

type LLMMetadata struct {
	ModelVersion  string       `json:"model_version"`
	FinishReason  string       `json:"finish_reason"`
	Tokens        UsageDetails `json:"tokens"`
	SafetyRatings []any        `json:"safety_ratings"`
	EvaluatedAt   string       `json:"evaluated_at"`
	FinishMessage *string      `json:"finish_message"`
}

type UsageDetails struct {
	Total     int              `json:"total"`
	Thoughts  int              `json:"thoughts"`
	Breakdown BreakdownByPhase `json:"breakdown"`
}

type BreakdownByPhase struct {
	Prompt     ModalityCount `json:"prompt"`
	Cache      ModalityCount `json:"cache"`
	Candidates ModalityCount `json:"candidates"`
	ToolUse    ModalityCount `json:"tool_use"`
}

type ModalityCount struct {
	Total int `json:"total"`
	Text  int `json:"text"`
	Video int `json:"video"`
	Audio int `json:"audio"`
	Image int `json:"image"`
	Doc   int `json:"doc"`
}
