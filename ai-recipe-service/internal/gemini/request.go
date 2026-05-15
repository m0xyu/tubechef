package gemini

import (
	"encoding/json"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
)

type requestPayload struct {
	Contents          []content          `json:"contents"`
	SystemInstruction systemInstructionP `json:"system_instruction"`
	GenerationConfig  *generationConfig  `json:"generationConfig,omitempty"`
}

type content struct {
	Parts []part `json:"parts"`
}

type part struct {
	Text     string    `json:"text,omitempty"`
	FileData *fileData `json:"file_data,omitempty"`
}

type fileData struct {
	FileURI string `json:"file_uri"`
}

type systemInstructionP struct {
	Parts []part `json:"parts"`
}

type thinkingConfig struct {
	ThinkingBudget int `json:"thinkingBudget"`
}

type generationConfig struct {
	ResponseMIMEType string          `json:"responseMimeType,omitempty"`
	ResponseSchema   any             `json:"responseSchema,omitempty"`
	ThinkingConfig   *thinkingConfig `json:"thinkingConfig,omitempty"`
}

func (c *Client) buildPayload(req domain.LLMRequest) ([]byte, error) {
	parts := []part{
		{Text: req.Prompt},
	}

	if len(req.MediaURIs) > 0 {
		parts = append(parts, part{
			FileData: &fileData{FileURI: req.MediaURIs[0]},
		})
	}

	payload := requestPayload{
		Contents: []content{
			{Parts: parts},
		},
		SystemInstruction: systemInstructionP{
			Parts: []part{{Text: req.SystemInstruction}},
		},
	}

	if req.Config != nil {
		genConfig := &generationConfig{
			ResponseMIMEType: req.Config.ResponseFormat,
			ResponseSchema:   req.Config.ResponseSchema,
		}

		if req.Config.ThinkingBudget != nil {
			genConfig.ThinkingConfig = &thinkingConfig{
				ThinkingBudget: *req.Config.ThinkingBudget,
			}
		}

		payload.GenerationConfig = genConfig
	}

	return json.Marshal(payload)
}
