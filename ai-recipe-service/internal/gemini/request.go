package gemini

import (
	"encoding/json"

	"github.com/m0xyu/tubechef/ai-recipe-service/domain"
)

const youTubeVideoBaseURL = "https://www.youtube.com/watch?v="

type requestPayload struct {
	Contents          []content          `json:"contents"`
	SystemInstruction systemInstructionP `json:"system_instruction"`
	GenerationConfig  generationConfig   `json:"generationConfig"`
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
	ResponseMIMEType string         `json:"responseMimeType"`
	ResponseSchema   responseSchema `json:"responseSchema"`
	ThinkingConfig   thinkingConfig `json:"thinkingConfig"`
}

func (c *Client) buildPayload(input domain.VideoInput) ([]byte, error) {
	payload := requestPayload{
		Contents: []content{
			{
				Parts: []part{
					{Text: buildPrompt(input)},
					{FileData: &fileData{FileURI: youTubeVideoBaseURL + input.VideoID}},
				},
			},
		},
		SystemInstruction: systemInstructionP{
			Parts: []part{{Text: systemInstruction}},
		},
		GenerationConfig: generationConfig{
			ResponseMIMEType: "application/json",
			ResponseSchema:   buildResponseSchema(),
			ThinkingConfig:   thinkingConfig{ThinkingBudget: 0},
		},
	}

	return json.Marshal(payload)
}
