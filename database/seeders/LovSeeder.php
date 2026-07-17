<?php

namespace Database\Seeders;

use App\Enums\LovType;
use App\Models\Lov;
use Illuminate\Database\Seeder;

class LovSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedTicketTypes();
        $this->seedTicketStatuses();
        $this->seedTicketPriorities();
        $this->seedCategories();
        $this->seedLabels();
    }

    private function seedTicketTypes(): void
    {
        $types = [
            ['value' => 'bug', 'name' => 'Bug', 'icon' => 'heroicon-o-bug-ant', 'color' => 'danger', 'is_default' => false],
            ['value' => 'story', 'name' => 'Story', 'icon' => 'heroicon-o-book-open', 'color' => 'success', 'is_default' => false],
            ['value' => 'task', 'name' => 'Task', 'icon' => 'heroicon-o-check-circle', 'color' => 'info', 'is_default' => true],
            ['value' => 'improvement', 'name' => 'Improvement', 'icon' => 'heroicon-o-arrow-trending-up', 'color' => 'warning', 'is_default' => false],
            ['value' => 'subtask', 'name' => 'Subtask', 'icon' => 'heroicon-o-list-bullet', 'color' => 'gray', 'is_default' => false],
        ];

        foreach ($types as $i => $type) {
            Lov::updateOrCreate(
                [
                    'team_id' => null,
                    'type' => LovType::TicketType,
                    'value' => $type['value'],
                ],
                [
                    'name' => $type['name'],
                    'icon' => $type['icon'],
                    'color' => $type['color'],
                    'sort_order' => $i,
                    'is_default' => $type['is_default'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedTicketStatuses(): void
    {
        $statuses = [
            ['value' => 'open', 'name' => 'Open', 'icon' => 'heroicon-o-inbox', 'color' => 'gray', 'is_default' => true, 'metadata' => ['is_resolved' => false]],
            ['value' => 'in_progress', 'name' => 'In Progress', 'icon' => 'heroicon-o-play', 'color' => 'info', 'is_default' => false, 'metadata' => ['is_resolved' => false]],
            ['value' => 'in_review', 'name' => 'In Review', 'icon' => 'heroicon-o-eye', 'color' => 'warning', 'is_default' => false, 'metadata' => ['is_resolved' => false]],
            ['value' => 'testing', 'name' => 'Testing', 'icon' => 'heroicon-o-beaker', 'color' => 'purple', 'is_default' => false, 'metadata' => ['is_resolved' => false]],
            ['value' => 'done', 'name' => 'Done', 'icon' => 'heroicon-o-check-circle', 'color' => 'success', 'is_default' => false, 'metadata' => ['is_resolved' => true]],
            ['value' => 'closed', 'name' => 'Closed', 'icon' => 'heroicon-o-x-circle', 'color' => 'gray', 'is_default' => false, 'metadata' => ['is_resolved' => true]],
        ];

        foreach ($statuses as $i => $status) {
            Lov::updateOrCreate(
                [
                    'team_id' => null,
                    'type' => LovType::TicketStatus,
                    'value' => $status['value'],
                ],
                [
                    'name' => $status['name'],
                    'icon' => $status['icon'],
                    'color' => $status['color'],
                    'sort_order' => $i,
                    'is_default' => $status['is_default'],
                    'is_active' => true,
                    'metadata' => $status['metadata'],
                ]
            );
        }
    }

    private function seedTicketPriorities(): void
    {
        $priorities = [
            ['value' => 'highest', 'name' => 'Highest', 'icon' => 'heroicon-o-chevron-double-up', 'color' => 'danger', 'is_default' => false],
            ['value' => 'high', 'name' => 'High', 'icon' => 'heroicon-o-chevron-up', 'color' => 'warning', 'is_default' => false],
            ['value' => 'medium', 'name' => 'Medium', 'icon' => 'heroicon-o-minus', 'color' => 'info', 'is_default' => true],
            ['value' => 'low', 'name' => 'Low', 'icon' => 'heroicon-o-chevron-down', 'color' => 'gray', 'is_default' => false],
            ['value' => 'lowest', 'name' => 'Lowest', 'icon' => 'heroicon-o-chevron-double-down', 'color' => 'gray', 'is_default' => false],
        ];

        foreach ($priorities as $i => $priority) {
            Lov::updateOrCreate(
                [
                    'team_id' => null,
                    'type' => LovType::TicketPriority,
                    'value' => $priority['value'],
                ],
                [
                    'name' => $priority['name'],
                    'icon' => $priority['icon'],
                    'color' => $priority['color'],
                    'sort_order' => $i,
                    'is_default' => $priority['is_default'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            ['value' => 'frontend', 'name' => 'Frontend', 'icon' => 'heroicon-o-computer-desktop', 'color' => 'info'],
            ['value' => 'backend', 'name' => 'Backend', 'icon' => 'heroicon-o-server', 'color' => 'success'],
            ['value' => 'database', 'name' => 'Database', 'icon' => 'heroicon-o-circle-stack', 'color' => 'warning'],
            ['value' => 'devops', 'name' => 'DevOps', 'icon' => 'heroicon-o-cloud', 'color' => 'purple'],
            ['value' => 'design', 'name' => 'Design', 'icon' => 'heroicon-o-paint-brush', 'color' => 'pink'],
            ['value' => 'documentation', 'name' => 'Documentation', 'icon' => 'heroicon-o-document-text', 'color' => 'gray'],
            ['value' => 'security', 'name' => 'Security', 'icon' => 'heroicon-o-shield-check', 'color' => 'danger'],
            ['value' => 'performance', 'name' => 'Performance', 'icon' => 'heroicon-o-bolt', 'color' => 'warning'],
        ];

        foreach ($categories as $i => $category) {
            Lov::updateOrCreate(
                [
                    'team_id' => null,
                    'type' => LovType::TicketCategory,
                    'value' => $category['value'],
                ],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'sort_order' => $i,
                    'is_default' => false,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedLabels(): void
    {
        $labels = [
            ['value' => 'urgent', 'name' => 'Urgent', 'icon' => 'heroicon-o-exclamation-triangle', 'color' => 'danger'],
            ['value' => 'blocked', 'name' => 'Blocked', 'icon' => 'heroicon-o-no-symbol', 'color' => 'danger'],
            ['value' => 'needs-review', 'name' => 'Needs Review', 'icon' => 'heroicon-o-eye', 'color' => 'warning'],
            ['value' => 'help-wanted', 'name' => 'Help Wanted', 'icon' => 'heroicon-o-hand-raised', 'color' => 'info'],
            ['value' => 'good-first-issue', 'name' => 'Good First Issue', 'icon' => 'heroicon-o-star', 'color' => 'success'],
            ['value' => 'wontfix', 'name' => "Won't Fix", 'icon' => 'heroicon-o-x-mark', 'color' => 'gray'],
            ['value' => 'duplicate', 'name' => 'Duplicate', 'icon' => 'heroicon-o-document-duplicate', 'color' => 'gray'],
            ['value' => 'enhancement', 'name' => 'Enhancement', 'icon' => 'heroicon-o-sparkles', 'color' => 'purple'],
        ];

        foreach ($labels as $i => $label) {
            Lov::updateOrCreate(
                [
                    'team_id' => null,
                    'type' => LovType::TicketLabel,
                    'value' => $label['value'],
                ],
                [
                    'name' => $label['name'],
                    'icon' => $label['icon'],
                    'color' => $label['color'],
                    'sort_order' => $i,
                    'is_default' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}
