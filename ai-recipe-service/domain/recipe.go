package domain

import (
	"time"
)

// Dto としての役割も持つ構造体群 --- IGNORE ---
type GenerateRecipeRequest struct {
	VideoID     string `json:"video_id" validate:"required"`
	VideoURL    string `json:"video_url" validate:"required"`
	Title       string `json:"title" validate:"required"`
	Description string `json:"description" validate:"required"`
}

type GenerateRecipeResponse struct {
	IsRecipe bool
	Recipe   *Recipe
}

// DBのテーブル構造を表す構造体群 --- IGNORE ---
type Recipe struct {
	ID          int64        `db:"id"`
	VideoID     int64        `db:"video_id"`
	Title       string       `db:"title"`
	Summary     string       `db:"summary"`
	DishID      *int64       `db:"dish_id"`
	ServingSize *string      `db:"serving_size"`
	CookingTime *string      `db:"cooking_time"`
	Ingredients []Ingredient `db:"-"` // 別テーブル
	Steps       []RecipeStep `db:"-"` // 別テーブル
	Tips        []RecipeTip  `db:"-"` // 別テーブル
	CreatedAt   time.Time    `db:"created_at"`
	UpdatedAt   time.Time    `db:"updated_at"`
}

type Ingredient struct {
	ID        int64     `db:"id"`
	RecipeID  int64     `db:"recipe_id"`
	Name      string    `db:"name"`
	Quantity  *string   `db:"quantity"`
	Group     *string   `db:"group"`
	Order     int       `db:"order"`
	CreatedAt time.Time `db:"created_at"`
	UpdatedAt time.Time `db:"updated_at"`
}

type RecipeStep struct {
	ID                 int64     `db:"id"`
	RecipeID           int64     `db:"recipe_id"`
	StepNumber         int       `db:"step_number"`
	Description        string    `db:"description"`
	StartTimeInSeconds *int      `db:"start_time_in_seconds"`
	EndTimeInSeconds   *int      `db:"end_time_in_seconds"`
	CreatedAt          time.Time `db:"created_at"`
	UpdatedAt          time.Time `db:"updated_at"`
}

type RecipeTip struct {
	ID                 int64     `db:"id"`
	RecipeID           int64     `db:"recipe_id"`
	Description        string    `db:"description"`
	RelatedStepNumber  *int      `db:"related_step_number"`
	StartTimeInSeconds *int      `db:"start_time_in_seconds"`
	CreatedAt          time.Time `db:"created_at"`
	UpdatedAt          time.Time `db:"updated_at"`
}

type Dish struct {
	ID          int64     `db:"id"`
	Name        string    `db:"name"`
	Slug        string    `db:"slug"`
	Description *string   `db:"description"`
	CreatedAt   time.Time `db:"created_at"`
	UpdatedAt   time.Time `db:"updated_at"`
}
