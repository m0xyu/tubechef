package domain

// VideoInput はレシピ生成に必要な動画メタデータ
type VideoInput struct {
	VideoID     string
	Title       string
	Description string
}

// GeneratedRecipe はLLMが生成したレシピデータ
type GeneratedRecipe struct {
	IsRecipe    bool         `json:"is_recipe"`
	Title       string       `json:"title"`
	Summary     string       `json:"summary"`
	DishName    string       `json:"dish_name"`
	DishSlug    string       `json:"dish_slug"`
	ServingSize *string      `json:"serving_size"`
	CookingTime *int         `json:"cooking_time"`
	Ingredients []Ingredient `json:"ingredients"`
	Steps       []RecipeStep `json:"steps"`
	Tips        []RecipeTip  `json:"tips"`
}

type Ingredient struct {
	Name     string  `json:"name"`
	Quantity *string `json:"quantity"`
	Group    *string `json:"group"`
	Order    int     `json:"order"`
}

type RecipeStep struct {
	StepNumber         int    `json:"step_number"`
	Description        string `json:"description"`
	StartTimeInSeconds *int   `json:"start_time_in_seconds"`
	EndTimeInSeconds   *int   `json:"end_time_in_seconds"`
}

type RecipeTip struct {
	Description        string `json:"description"`
	RelatedStepNumber  *int   `json:"related_step_number"`
	StartTimeInSeconds *int   `json:"start_time_in_seconds"`
}
