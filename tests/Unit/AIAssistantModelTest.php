<?php

namespace Tests\Unit;

use App\Models\AIAssistant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIAssistantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_assistant_can_be_created()
    {
        $assistant = AIAssistant::factory()->create([
            'name' => 'Test Assistant',
            'type' => 'tutor',
        ]);

        $this->assertInstanceOf(AIAssistant::class, $assistant);
        $this->assertEquals('Test Assistant', $assistant->name);
        $this->assertEquals('tutor', $assistant->type);
        $this->assertEquals('active', $assistant->status);
    }

    public function test_ai_assistant_availability_check()
    {
        $assistant = AIAssistant::factory()->create([
            'api_usage_limit' => 1000,
            'api_usage_current' => 500,
        ]);

        $this->assertTrue($assistant->isAvailable());

        $assistant->update(['api_usage_current' => 1000]);
        $this->assertFalse($assistant->isAvailable());

        $assistant->update(['status' => 'maintenance']);
        $this->assertFalse($assistant->isAvailable());
    }

    public function test_ai_assistant_usage_tracking()
    {
        $assistant = AIAssistant::factory()->create([
            'api_usage_limit' => 1000,
            'api_usage_current' => 0,
        ]);

        $assistant->incrementUsage(100);
        
        $this->assertEquals(100, $assistant->fresh()->api_usage_current);
        $this->assertEquals(10.0, $assistant->getUsagePercentage());

        $assistant->resetUsage();
        $this->assertEquals(0, $assistant->fresh()->api_usage_current);
    }

    public function test_ai_assistant_capabilities()
    {
        $assistant = AIAssistant::factory()->create([
            'capabilities' => ['text_generation', 'question_answering'],
        ]);

        $this->assertTrue($assistant->hasCapability('text_generation'));
        $this->assertTrue($assistant->hasCapability('question_answering'));
        $this->assertFalse($assistant->hasCapability('grading'));
    }

    public function test_ai_assistant_access_control()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $privateAssistant = AIAssistant::factory()->create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);

        $publicAssistant = AIAssistant::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        $this->assertTrue($privateAssistant->canBeUsedBy($owner));
        $this->assertFalse($privateAssistant->canBeUsedBy($otherUser));

        $this->assertTrue($publicAssistant->canBeUsedBy($owner));
        $this->assertTrue($publicAssistant->canBeUsedBy($otherUser));
    }

    public function test_ai_assistant_scopes()
    {
        $user = User::factory()->create();
        
        // Create assistants with different states
        $activeAssistant = AIAssistant::factory()->active()->tutor()->create([
            'user_id' => $user->id,
        ]);

        $inactiveAssistant = AIAssistant::factory()->inactive()->tutor()->create([
            'user_id' => $user->id,
        ]);

        $publicAssistant = AIAssistant::factory()->active()->public()->tutor()->create([
            'user_id' => $user->id,
        ]);

        // Test scopes work
        $this->assertEquals('active', $activeAssistant->status);
        $this->assertEquals('inactive', $inactiveAssistant->status);
        $this->assertTrue($publicAssistant->is_public);
        
        // Test scope methods return results
        $activeCount = AIAssistant::active()->count();
        $publicCount = AIAssistant::public()->count();
        $tutorCount = AIAssistant::byType('tutor')->count();
        
        // Verify counts are reasonable
        $this->assertGreaterThan(0, $activeCount);
        $this->assertGreaterThan(0, $publicCount);
        $this->assertGreaterThan(0, $tutorCount);
    }
}
