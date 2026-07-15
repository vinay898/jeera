<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create or get the test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        // Create a team and attach the user
        $team = Team::factory()->create([
            'name' => 'Demo Team',
            'slug' => 'demo-team',
        ]);
        $user->teams()->syncWithoutDetaching([$team->id]);

        // Create a default workflow for the team
        $workflow = Workflow::factory()->create([
            'team_id' => $team->id,
            'name' => 'Default Workflow',
        ]);

        // Create sample projects
        $projects = Project::factory(3)->create([
            'team_id' => $team->id,
            'workflow_id' => $workflow->id,
            'lead_user_id' => $user->id,
        ]);

        // Create sample tickets for each project
        foreach ($projects as $project) {
            Ticket::factory(5)->create([
                'team_id' => $team->id,
                'project_id' => $project->id,
                'reporter_id' => $user->id,
            ]);
        }
    }
}
