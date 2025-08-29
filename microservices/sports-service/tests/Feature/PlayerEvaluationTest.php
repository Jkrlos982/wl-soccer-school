<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\School;
use App\Models\Category;
use App\Models\Player;
use App\Models\Training;
use App\Models\PlayerEvaluation;
use Carbon\Carbon;

class PlayerEvaluationTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected $user;
    protected $school;
    protected $category;
    protected $player;
    protected $training;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->school = School::factory()->create();
        $this->category = Category::factory()->create([
            'school_id' => $this->school->id
        ]);
        $this->player = Player::factory()->create([
            'school_id' => $this->school->id,
            'category_id' => $this->category->id
        ]);
        $this->training = Training::factory()->create([
            'school_id' => $this->school->id,
            'category_id' => $this->category->id
        ]);
    }
    
    public function test_can_create_player_evaluation()
    {
        $evaluationData = [
            'school_id' => $this->school->id,
            'player_id' => $this->player->id,
            'evaluator_id' => $this->user->id,
            'training_id' => $this->training->id,
            'evaluation_date' => Carbon::now()->format('Y-m-d'),
            'evaluation_type' => 'training',
            'technical_skills' => 8,
            'ball_control' => 7,
            'passing' => 8,
            'shooting' => 6,
            'dribbling' => 7,
            'speed' => 8,
            'endurance' => 7,
            'strength' => 6,
            'agility' => 8,
            'positioning' => 7,
            'decision_making' => 6,
            'teamwork' => 9,
            'game_understanding' => 7,
            'attitude' => 9,
            'discipline' => 8,
            'leadership' => 6,
            'commitment' => 9,
            'overall_rating' => 7.5,
            'strengths' => 'Excellent ball control and teamwork',
            'areas_for_improvement' => 'Needs to improve shooting accuracy',
            'general_comments' => 'Great potential, very coachable player',
            'short_term_goals' => 'Improve shooting technique',
            'long_term_goals' => 'Become a key player in the team'
        ];
        
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/player-evaluations', $evaluationData);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'school_id',
                    'player_id',
                    'evaluator_id',
                    'training_id',
                    'evaluation_date',
                    'evaluation_type',
                    'technical_skills',
                    'overall_rating',
                    'technical_average',
                    'physical_average',
                    'tactical_average',
                    'mental_average'
                ]
            ]);
        
        $this->assertDatabaseHas('player_evaluations', [
            'player_id' => $this->player->id,
            'evaluator_id' => $this->user->id,
            'evaluation_type' => 'training'
        ]);
    }
    
    public function test_can_get_player_evaluations()
    {
        // Create some evaluations
        PlayerEvaluation::factory()->count(3)->create([
            'school_id' => $this->school->id,
            'player_id' => $this->player->id,
            'evaluator_id' => $this->user->id
        ]);
        
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/player-evaluations');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'school_id',
                        'player_id',
                        'evaluator_id',
                        'evaluation_date',
                        'evaluation_type',
                        'overall_rating'
                    ]
                ],
                'meta' => [
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page'
                    ]
                ]
            ]);
    }
    
    public function test_can_get_player_specific_evaluations()
    {
        // Create evaluations for this player
        PlayerEvaluation::factory()->count(2)->create([
            'school_id' => $this->school->id,
            'player_id' => $this->player->id,
            'evaluator_id' => $this->user->id
        ]);
        
        // Create evaluations for another player
        $anotherPlayer = Player::factory()->create([
            'school_id' => $this->school->id,
            'category_id' => $this->category->id
        ]);
        PlayerEvaluation::factory()->create([
            'school_id' => $this->school->id,
            'player_id' => $anotherPlayer->id,
            'evaluator_id' => $this->user->id
        ]);
        
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/players/{$this->player->id}/evaluations");
        
        $response->assertStatus(200);
        
        $evaluations = $response->json('data');
        $this->assertCount(2, $evaluations);
        
        // Verify all evaluations belong to the correct player
        foreach ($evaluations as $evaluation) {
            $this->assertEquals($this->player->id, $evaluation['player_id']);
        }
    }
    
    public function test_can_get_player_evaluation_stats()
    {
        // Create evaluations with different ratings
        PlayerEvaluation::factory()->create([
            'school_id' => $this->school->id,
            'player_id' => $this->player->id,
            'evaluator_id' => $this->user->id,
            'technical_skills' => 8,
            'physical_skills' => 7,
            'tactical_skills' => 6,
            'mental_skills' => 9,
            'overall_rating' => 7.5
        ]);
        
        PlayerEvaluation::factory()->create([
            'school_id' => $this->school->id,
            'player_id' => $this->player->id,
            'evaluator_id' => $this->user->id,
            'technical_skills' => 6,
            'physical_skills' => 8,
            'tactical_skills' => 7,
            'mental_skills' => 8,
            'overall_rating' => 7.0
        ]);
        
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/players/{$this->player->id}/evaluation-stats");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'player_id',
                    'total_evaluations',
                    'average_overall_rating',
                    'latest_evaluation_date',
                    'skill_averages' => [
                        'technical_average',
                        'physical_average',
                        'tactical_average',
                        'mental_average'
                    ],
                    'evaluation_trend',
                    'evaluation_types_count'
                ]
            ]);
    }
    
    public function test_validation_requires_at_least_one_skill_rating()
    {
        $evaluationData = [
            'school_id' => $this->school->id,
            'player_id' => $this->player->id,
            'evaluator_id' => $this->user->id,
            'evaluation_date' => Carbon::now()->format('Y-m-d'),
            'evaluation_type' => 'training',
            'overall_rating' => 7.5
            // No skill ratings provided
        ];
        
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/player-evaluations', $evaluationData);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['skills']);
    }
    
    public function test_validation_prevents_future_evaluation_date()
    {
        $evaluationData = [
            'school_id' => $this->school->id,
            'player_id' => $this->player->id,
            'evaluator_id' => $this->user->id,
            'evaluation_date' => Carbon::now()->addDay()->format('Y-m-d'), // Future date
            'evaluation_type' => 'training',
            'technical_skills' => 8,
            'overall_rating' => 7.5
        ];
        
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/player-evaluations', $evaluationData);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['evaluation_date']);
    }
}