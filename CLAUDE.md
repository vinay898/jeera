# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Jeera** is a full-featured service ticket and project management system (Jira replacement) built with Laravel 13, Filament v5, and Livewire 4. The system provides ticket management, Kanban/Scrum boards, time tracking, SLA management, custom workflows, and AI-powered automation.

**Current Status**: Specification phase - [SERVICE_TICKET_PROJECT.md](SERVICE_TICKET_PROJECT.md) contains the complete blueprint. Implementation is in progress.

## Development Commands

### Composer Scripts (Primary Commands)
```bash
composer run setup    # Full setup: dependencies, migrations, npm install, build
composer run dev      # Development server with all services running
composer run test     # Run test suite with compact output
composer run lint     # Code formatting and linting (Laravel Pint)
```

### Individual Commands
```bash
# Development environment (run concurrently)
php artisan serve              # Laravel dev server
php artisan queue:listen       # Job queue processor
php artisan pail               # Real-time log monitoring
npm run dev                    # Vite asset dev server

# Database
php artisan migrate            # Run migrations

# Model generation pattern
php artisan make:model Project -mfs    # Model + migration + factory + seeder

# Filament resources
php artisan make:filament-resource Project
```

## Filament Blueprint Setup

**Filament Blueprint** is a premium Laravel Boost extension for creating detailed Filament implementation plans and security audits.

### Installation

1. **Add license key to .env**:
```env
FILAMENT_BLUEPRINT_LICENSE=your_license_key_here
```

2. **Configure Composer authentication**:
```bash
composer config repositories.filament composer https://packages.filamentphp.com/composer
composer config --auth http-basic.packages.filamentphp.com "your@email.com" "${FILAMENT_BLUEPRINT_LICENSE}"
```

3. **Install the package**:
```bash
composer require filament/blueprint --dev
php artisan boost:install
```

### Usage

**Planning Mode**: Request a Filament Blueprint in planning mode to generate detailed specifications covering models, resources, forms, tables, authorization, and testing.

**Security Audits**: Invoke the `filament-security-audit` skill to scan for authorization issues, file upload vulnerabilities, XSS risks, and other security concerns.

## Tech Stack

### Core
- **PHP**: 8.3+
- **Laravel**: 13.x
- **Database**: SQLite (dev) / MySQL/PostgreSQL (prod)

### Frontend & UI
- **Filament**: v5.6+ - Admin panel & CRUD
- **Livewire**: v4.1+ - Reactive components
- **Flux UI**: v2.x - Component library
- **Tailwind CSS**: v4.x - Utility-first styling
- **Alpine.js**: Client-side interactivity
- **Vite**: 8.x - Asset bundling

### AI & Authentication
- **Prism PHP**: ^0.100 - LLM abstraction (OpenAI/Anthropic)
- **Laravel Fortify**: v1.x - Authentication scaffolding

### Development & Testing
- **PHPUnit**: v12+ (NOT Pest)
- **Laravel Pint**: Code formatting
- **Laravel Pail**: Real-time logs
- **Laravel Boost**: MCP server
- **Filament Blueprint**: Premium planning extension

### Queue System
- **Driver**: Database
- **Job Batching**: Enabled
- **Failed Jobs**: Database tracking

## Architecture Patterns

### Request Flow
```
Request → Filament Resource / Livewire Component
    ↓
Service Class (TicketService, SprintService, etc.)
    ↓
Eloquent Model (with SoftDeletes)
    ↓
Database
```

### Multi-Tenancy
- **Tenant Model**: `Team` (with `slug` attribute)
- **Isolation**: All models include `team_id` foreign key
- **Filament Config**: `->tenant(Team::class, slugAttribute: 'slug')`

### Directory Structure
```
app/
├── Ai/
│   ├── Tools/                    # AI tool implementations
│   └── ToolRegistry.php          # Tool discovery mechanism
├── Services/
│   ├── Ai/AgentService.php       # LLM orchestration
│   ├── TicketService.php         # Ticket business logic
│   ├── SprintService.php         # Sprint management
│   ├── SlaService.php            # SLA calculations
│   └── WorkflowService.php       # Automation engine
├── Livewire/
│   ├── KanbanBoard.php           # Drag-drop board
│   ├── SprintBoard.php           # Scrum board
│   ├── TicketModal.php           # Quick view/edit modal
│   └── TimeLogger.php            # Time tracking widget
├── Filament/
│   ├── Resources/                # CRUD interfaces
│   ├── Pages/                    # Custom pages (Dashboard, Backlog, Reports)
│   └── Widgets/                  # Dashboard widgets
├── Models/
├── Enums/
├── Observers/                    # Event listeners for models
└── Jobs/                         # Queue jobs
```

### Event-Driven Patterns
- **Observers**: Track changes (TicketObserver, SprintObserver, SlaObserver)
- **Queue Jobs**: Async processing (notifications, SLA checks, report generation)
- **Model Events**: History logging, automation triggers, notification dispatch

## Domain Model

### Hierarchy
```
Team (tenant)
├── Projects
│   ├── Epics
│   │   └── Stories (tickets with type='story')
│   │       └── Subtasks (tickets with parent_id)
│   ├── Tickets (bugs, tasks, improvements)
│   ├── Sprints
│   └── Labels
├── Users (team_user pivot)
├── CustomFields
├── Workflows
└── SlaConfigurations
```

### Core Tables
- **projects**: `id, team_id, name, key, workflow_id, lead_user_id`
- **tickets**: `id, team_id, project_id, epic_id, parent_id, sprint_id, key, title, type, status, priority, assignee_id, story_points, labels (json), custom_fields (json)`
- **epics**: `id, team_id, project_id, key, title, status, start_date, end_date`
- **sprints**: `id, team_id, project_id, name, goal, start_date, end_date, status`
- **comments**: `id, ticket_id, user_id, body, is_internal`
- **time_logs**: `id, ticket_id, user_id, started_at, ended_at, duration_minutes`
- **sla_configurations**: `id, team_id, project_id, priority, response_time_minutes, resolution_time_minutes`
- **workflows**: `id, team_id, name, statuses (json), transitions (json)`

### Key Enums
```php
enum TicketType: string { Bug, Story, Task, Improvement, Subtask }
enum TicketStatus: string { Open, InProgress, InReview, Testing, Done, Closed }
enum TicketPriority: string { Highest, High, Medium, Low, Lowest }
enum SprintStatus: string { Planning, Active, Completed }
```

## Coding Conventions

### PHP
- Use constructor property promotion
- Explicit return types on all methods
- TitleCase for Enum keys
- PHPDoc blocks over inline comments
- Curly braces on all control structures

### Models
```php
#[Fillable(['team_id', 'project_id', 'title'])]
class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'labels' => 'array',
            'custom_fields' => 'array',
            'due_date' => 'date',
        ];
    }
}
```

### Filament v5
- Split forms/tables into `Schemas/` and `Tables/` classes
- Use `Section` from `Filament\Schemas\Components`
- Tables use closures: `->records(fn () => $data)`
- Test with `->loadTable()` before assertions

### Livewire v4
- Public properties for component state
- `#[On('event')]` attribute for event listeners
- `wire:sortable` for drag-drop functionality

### Testing
- Use PHPUnit (NOT Pest)
- Feature tests primarily
- Factories for model creation
- `RefreshDatabase` trait
- Sync queue in tests: `Queue::fake()`

## MCP Server Tools

### Laravel Boost Configuration
```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "php",
      "args": ["vendor/bin/boost", "mcp"]
    }
  }
}
```

### Available Tools
- `database-schema` - Inspect table structure
- `database-query` - Read-only SQL queries
- `search-docs` - Version-specific Laravel/Filament documentation
- `browser-logs` - Frontend debugging
- `last-error` - Backend error tracking
- `read-log-entries` - Application logs
- `application-info` - Package versions
- `get-absolute-url` - URL resolution

## Skills Activation

Activate domain-specific skills when working in these areas:

| Skill | When to Activate |
|-------|------------------|
| `fluxui-development` | Working with `<flux:*>` components |
| `livewire-development` | Livewire components, `wire:` directives |
| `tailwindcss-development` | Tailwind utility classes |
| `fortify-development` | Authentication features |
| `laravel-best-practices` | Backend PHP patterns |
| `filament-security-audit` | Security audits for Filament admin panel |

## AI Tools to Implement

Located in `app/Ai/Tools/`:
- `SearchTicketsTool` - Find tickets by criteria
- `CreateTicketTool` - Create new tickets
- `UpdateTicketTool` - Modify ticket fields
- `MoveTicketTool` - Change status/sprint
- `AssignTicketTool` - Assign to user
- `LogTimeTool` - Record time entry
- `CreateSprintTool` - Start new sprint
- `GetSprintStatusTool` - Sprint progress summary
- `SearchProjectsTool` - Find projects
- `GetBacklogTool` - List backlog items

## Environment Variables

```env
APP_NAME="Service Tickets"
APP_ENV=local
DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
LOG_CHANNEL=stack

# AI Integration (optional)
OPENAI_API_KEY=

# Filament Blueprint (required for premium features)
FILAMENT_BLUEPRINT_LICENSE=
```

## Critical Files

- **[SERVICE_TICKET_PROJECT.md](SERVICE_TICKET_PROJECT.md)** - Complete project specification with feature details, database schema, and implementation requirements
- **[composer.json](composer.json)** - Dependencies and scripts
- **[package.json](package.json)** - Frontend dependencies

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
