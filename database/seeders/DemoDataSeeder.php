<?php

namespace Database\Seeders;

use App\Models\Epic;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\ProjectRequestHistory;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketLink;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Users ──────────────────────────────────────────────
        $superAdmin = User::where('email', 'superadmin@example.com')->first();
        $cto        = User::where('email', 'cto@example.com')->first();
        $manager    = User::where('email', 'manager@example.com')->first();
        $analyst    = User::where('email', 'analyst@example.com')->first();
        $programmer = User::where('email', 'programmer@example.com')->first();
        $qa         = User::where('email', 'qa@example.com')->first();

        if (!$cto || !$manager || !$analyst || !$programmer || !$qa) {
            $this->command->error('Run DatabaseSeeder first to create test users.');
            return;
        }

        // ── Ticket Priorities ──────────────────────────────────
        $priorityLow      = TicketPriority::firstOrCreate(['name' => 'Low'], ['color' => '#10B981']);
        $priorityMedium   = TicketPriority::firstOrCreate(['name' => 'Medium'], ['color' => '#F59E0B']);
        $priorityHigh     = TicketPriority::firstOrCreate(['name' => 'High'], ['color' => '#EF4444']);
        $priorityCritical = TicketPriority::firstOrCreate(['name' => 'Critical'], ['color' => '#DC2626']);

        // ════════════════════════════════════════════════════════
        // PROJECT 1: E-Commerce — SDLC phase: Implementation
        // Full flow: request approved → project created → sudah di phase implementation
        // ════════════════════════════════════════════════════════

        $req1 = ProjectRequest::create([
            'title' => 'E-Commerce Platform',
            'description' => '<p>Membangun platform e-commerce lengkap dengan fitur product catalog, shopping cart, checkout, dan payment gateway integration.</p>',
            'business_justification' => '<p>Perusahaan perlu channel penjualan online untuk meningkatkan revenue. Target market: B2C customer di Indonesia. Estimasi revenue tambahan Rp 500jt/bulan setelah launch.</p>',
            'priority' => 'high',
            'requested_deadline' => '2026-12-31',
            'status' => 'approved',
            'requested_by' => $manager->id,
            'analyst_id' => $analyst->id,
            'requirement_analysis' => '<p><strong>Functional Requirements:</strong></p><ul><li>Product management (CRUD, categories, variants)</li><li>Shopping cart dengan session management</li><li>Checkout flow multi-step</li><li>Payment gateway: Midtrans, Xendit</li><li>Order management & tracking</li><li>Customer account & address book</li></ul><p><strong>Non-Functional:</strong></p><ul><li>Handle 10,000 concurrent users</li><li>Page load < 2 detik</li><li>99.9% uptime SLA</li></ul>',
            'feasibility_study' => '<p><strong>Technical Feasibility:</strong> Tim sudah familiar dengan Laravel + Vue.js stack. Payment gateway API tersedia dan well-documented.</p><p><strong>Resource:</strong> Butuh 3 programmer + 1 QA selama 6 bulan.</p><p><strong>Cost Estimation:</strong> Rp 180jt (development) + Rp 15jt/bulan (infrastructure)</p><p><strong>Kesimpulan:</strong> Feasible dengan timeline 6 bulan.</p>',
            'technical_notes' => '<p>Stack: Laravel 12 + Vue 3 + Inertia.js. Database: MySQL + Redis cache. Search: Meilisearch. Storage: S3 untuk product images. Deployment: Docker + Kubernetes di AWS.</p>',
            'analysis_submitted_at' => '2026-06-15 10:30:00',
            'manager_recommendation' => 'approve',
            'manager_recommendation_reason' => '<p>Projek ini sangat strategis untuk pertumbuhan bisnis. Analisis requirement sudah lengkap, feasibility study menunjukkan tim mampu. Budget sudah di-approve oleh finance. Recommend untuk proceed.</p>',
            'recommended_by' => $manager->id,
            'recommended_at' => '2026-06-18 14:00:00',
            'cto_decision' => 'approve',
            'cto_decision_reason' => '<p>Approved. Stack yang dipilih tepat. Pastikan implementasi payment gateway mengikuti PCI DSS compliance. Tambahkan monitoring dengan Sentry + Grafana dari awal.</p>',
            'decided_by' => $cto->id,
            'decided_at' => '2026-06-20 09:00:00',
        ]);

        $project1 = Project::create([
            'name' => 'E-Commerce Platform',
            'description' => '<p>Platform e-commerce lengkap dengan product catalog, shopping cart, checkout, dan payment gateway integration.</p>',
            'ticket_prefix' => 'ECOM',
            'color' => '#3B82F6',
            'sdlc_phase' => 'implementation',
            'project_request_id' => $req1->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        $req1->update(['project_id' => $project1->id]);

        // Statuses
        $s1Backlog    = $project1->ticketStatuses()->create(['name' => 'Backlog', 'color' => '#6B7280', 'sort_order' => 0]);
        $s1Todo       = $project1->ticketStatuses()->create(['name' => 'To Do', 'color' => '#F59E0B', 'sort_order' => 1]);
        $s1InProgress = $project1->ticketStatuses()->create(['name' => 'In Progress', 'color' => '#3B82F6', 'sort_order' => 2]);
        $s1Review     = $project1->ticketStatuses()->create(['name' => 'Review', 'color' => '#8B5CF6', 'sort_order' => 3]);
        $s1Done       = $project1->ticketStatuses()->create(['name' => 'Done', 'color' => '#10B981', 'sort_order' => 4, 'is_completed' => true]);

        // Members
        $project1->members()->syncWithoutDetaching([$manager->id, $analyst->id, $cto->id, $programmer->id, $qa->id]);

        // Epics
        $epic1a = Epic::create(['project_id' => $project1->id, 'name' => 'Product Management', 'description' => 'CRUD product, categories, variants', 'start_date' => '2026-07-01', 'end_date' => '2026-08-15', 'sort_order' => 0]);
        $epic1b = Epic::create(['project_id' => $project1->id, 'name' => 'Shopping Cart & Checkout', 'description' => 'Cart, checkout flow, payment', 'start_date' => '2026-08-16', 'end_date' => '2026-10-31', 'sort_order' => 1]);
        $epic1c = Epic::create(['project_id' => $project1->id, 'name' => 'Order Management', 'description' => 'Order tracking, history, notifications', 'start_date' => '2026-11-01', 'end_date' => '2026-12-15', 'sort_order' => 2]);

        // Tickets — Product Management (mostly done)
        $t1 = Ticket::create(['project_id' => $project1->id, 'ticket_status_id' => $s1Done->id, 'priority_id' => $priorityHigh->id, 'epic_id' => $epic1a->id, 'name' => 'Setup database schema untuk products', 'description' => '<p>Design dan implementasi migration untuk tabel products, categories, product_variants, product_images.</p>', 'start_date' => '2026-07-01', 'due_date' => '2026-07-05', 'created_by' => $manager->id]);
        $t1->assignees()->sync([$programmer->id]);

        $t2 = Ticket::create(['project_id' => $project1->id, 'ticket_status_id' => $s1Done->id, 'priority_id' => $priorityHigh->id, 'epic_id' => $epic1a->id, 'name' => 'API CRUD Product', 'description' => '<p>REST API untuk create, read, update, delete product. Include image upload ke S3.</p>', 'start_date' => '2026-07-06', 'due_date' => '2026-07-15', 'created_by' => $manager->id]);
        $t2->assignees()->sync([$programmer->id]);

        $t3 = Ticket::create(['project_id' => $project1->id, 'ticket_status_id' => $s1Done->id, 'priority_id' => $priorityMedium->id, 'epic_id' => $epic1a->id, 'name' => 'Frontend halaman product listing', 'description' => '<p>Halaman listing product dengan filter, search, sorting, dan pagination.</p>', 'start_date' => '2026-07-10', 'due_date' => '2026-07-20', 'created_by' => $manager->id]);
        $t3->assignees()->sync([$programmer->id]);

        $t4 = Ticket::create(['project_id' => $project1->id, 'ticket_status_id' => $s1Review->id, 'priority_id' => $priorityMedium->id, 'epic_id' => $epic1a->id, 'name' => 'Product detail page dengan variant selector', 'description' => '<p>Halaman detail product dengan image gallery, variant selection, dan add to cart button.</p>', 'start_date' => '2026-07-15', 'due_date' => '2026-07-25', 'created_by' => $manager->id]);
        $t4->assignees()->sync([$programmer->id, $qa->id]);

        // Tickets — Shopping Cart (in progress)
        $t5 = Ticket::create(['project_id' => $project1->id, 'ticket_status_id' => $s1InProgress->id, 'priority_id' => $priorityCritical->id, 'epic_id' => $epic1b->id, 'name' => 'Shopping cart backend & session management', 'description' => '<p>Implementasi shopping cart dengan Redis session. Support guest cart dan merge saat login.</p>', 'start_date' => '2026-07-20', 'due_date' => '2026-08-01', 'created_by' => $manager->id]);
        $t5->assignees()->sync([$programmer->id]);

        $t6 = Ticket::create(['project_id' => $project1->id, 'ticket_status_id' => $s1InProgress->id, 'priority_id' => $priorityHigh->id, 'epic_id' => $epic1b->id, 'name' => 'Integrasi Midtrans payment gateway', 'description' => '<p>Integrasi Midtrans Snap untuk payment processing. Support: credit card, bank transfer, e-wallet.</p>', 'start_date' => '2026-07-25', 'due_date' => '2026-08-10', 'created_by' => $manager->id]);
        $t6->assignees()->sync([$programmer->id]);

        $t7 = Ticket::create(['project_id' => $project1->id, 'ticket_status_id' => $s1Todo->id, 'priority_id' => $priorityHigh->id, 'epic_id' => $epic1b->id, 'name' => 'Checkout flow multi-step', 'description' => '<p>Step 1: Address → Step 2: Shipping → Step 3: Payment → Step 4: Confirmation</p>', 'start_date' => '2026-08-05', 'due_date' => '2026-08-20', 'created_by' => $manager->id]);
        $t7->assignees()->sync([$programmer->id]);

        // Tickets — Order Management (backlog)
        $t8 = Ticket::create(['project_id' => $project1->id, 'ticket_status_id' => $s1Backlog->id, 'priority_id' => $priorityMedium->id, 'epic_id' => $epic1c->id, 'name' => 'Order tracking system', 'description' => '<p>Real-time order status tracking untuk customer.</p>', 'start_date' => '2026-11-01', 'due_date' => '2026-11-15', 'created_by' => $manager->id]);
        $t8->assignees()->sync([$programmer->id]);

        $t9 = Ticket::create(['project_id' => $project1->id, 'ticket_status_id' => $s1Backlog->id, 'priority_id' => $priorityLow->id, 'epic_id' => $epic1c->id, 'name' => 'Email notification untuk order updates', 'description' => '<p>Kirim email notifikasi saat: order created, payment confirmed, shipped, delivered.</p>', 'start_date' => '2026-11-15', 'due_date' => '2026-11-30', 'created_by' => $manager->id]);

        // Comments
        TicketComment::withoutEvents(fn () => TicketComment::create(['ticket_id' => $t5->id, 'user_id' => $programmer->id, 'comment' => '<p>Redis session sudah working. Sedang handle edge case: cart merge saat guest login.</p>']));
        TicketComment::withoutEvents(fn () => TicketComment::create(['ticket_id' => $t5->id, 'user_id' => $manager->id, 'comment' => '<p>Pastikan cart tidak hilang kalau session expired. Mungkin perlu persist ke database juga sebagai fallback.</p>']));
        TicketComment::withoutEvents(fn () => TicketComment::create(['ticket_id' => $t6->id, 'user_id' => $programmer->id, 'comment' => '<p>Midtrans sandbox sudah connect. Credit card flow working. Masih perlu test bank transfer callback.</p>']));
        TicketComment::withoutEvents(fn () => TicketComment::create(['ticket_id' => $t4->id, 'user_id' => $qa->id, 'comment' => '<p>Bug: variant selector tidak update harga saat ganti variant. Tolong fix dulu sebelum merge.</p>']));

        // Links
        TicketLink::create(['ticket_id' => $t6->id, 'title' => 'Midtrans API Docs', 'url' => 'https://docs.midtrans.com', 'added_by' => $programmer->id]);
        TicketLink::create(['ticket_id' => $t6->id, 'title' => 'Midtrans Dashboard Sandbox', 'url' => 'https://dashboard.sandbox.midtrans.com', 'added_by' => $programmer->id]);
        TicketLink::create(['ticket_id' => $t1->id, 'title' => 'ERD Diagram', 'url' => 'https://dbdiagram.io/d/ecommerce-schema', 'added_by' => $analyst->id]);

        // Request history
        ProjectRequestHistory::create(['project_request_id' => $req1->id, 'user_id' => $manager->id, 'action' => 'created', 'from_status' => null, 'to_status' => 'draft', 'notes' => 'Request created']);
        ProjectRequestHistory::create(['project_request_id' => $req1->id, 'user_id' => $manager->id, 'action' => 'assigned_analyst', 'from_status' => 'draft', 'to_status' => 'pending_analysis', 'notes' => "Assigned {$analyst->name} as analyst"]);
        ProjectRequestHistory::create(['project_request_id' => $req1->id, 'user_id' => $analyst->id, 'action' => 'analysis_submitted', 'from_status' => 'pending_analysis', 'to_status' => 'analysis_submitted']);
        ProjectRequestHistory::create(['project_request_id' => $req1->id, 'user_id' => $manager->id, 'action' => 'recommended', 'from_status' => 'analysis_submitted', 'to_status' => 'recommended_approve', 'notes' => 'Recommended: approve']);
        ProjectRequestHistory::create(['project_request_id' => $req1->id, 'user_id' => $cto->id, 'action' => 'approved', 'from_status' => 'recommended_approve', 'to_status' => 'approved', 'notes' => "Approved. Project 'E-Commerce Platform' created."]);

        $this->command->info('✓ Project 1: E-Commerce Platform (Implementation phase, 9 tickets)');

        // ════════════════════════════════════════════════════════
        // PROJECT 2: HR Management System — SDLC phase: Testing
        // ════════════════════════════════════════════════════════

        $req2 = ProjectRequest::create([
            'title' => 'HR Management System',
            'description' => '<p>Sistem manajemen HR untuk mengelola data karyawan, absensi, cuti, payroll, dan performance review.</p>',
            'business_justification' => '<p>Proses HR saat ini masih manual menggunakan spreadsheet. Terjadi banyak error dalam perhitungan gaji dan tracking cuti. Sistem ini akan menghemat 40 jam/bulan kerja HR team.</p>',
            'priority' => 'critical',
            'requested_deadline' => '2026-09-30',
            'status' => 'approved',
            'requested_by' => $manager->id,
            'analyst_id' => $analyst->id,
            'requirement_analysis' => '<p><strong>Modul Utama:</strong></p><ul><li>Employee database & profile management</li><li>Attendance tracking (fingerprint integration)</li><li>Leave management (approval workflow)</li><li>Payroll calculation & slip generation</li><li>Performance review (KPI-based)</li></ul>',
            'feasibility_study' => '<p>Tim sudah punya pengalaman build sistem internal. Integrasi fingerprint device bisa pakai SDK vendor. Payroll calculation perlu konsultasi dengan accounting team. Timeline: 4 bulan realistic.</p>',
            'technical_notes' => '<p>Stack: Laravel + Filament (admin panel). Database: PostgreSQL. Fingerprint: ZKTeco SDK. PDF: DomPDF untuk payslip.</p>',
            'analysis_submitted_at' => '2026-04-10 11:00:00',
            'manager_recommendation' => 'approve',
            'manager_recommendation_reason' => '<p>Urgent. Tim HR sudah sangat overwhelmed dengan proses manual. ROI jelas — penghematan waktu dan pengurangan error payroll.</p>',
            'recommended_by' => $manager->id,
            'recommended_at' => '2026-04-12 09:30:00',
            'cto_decision' => 'approve',
            'cto_decision_reason' => '<p>Approved. Prioritaskan modul attendance dan leave management dulu. Payroll bisa phase 2 kalau timeline mepet.</p>',
            'decided_by' => $cto->id,
            'decided_at' => '2026-04-14 10:00:00',
        ]);

        $project2 = Project::create([
            'name' => 'HR Management System',
            'description' => '<p>Sistem manajemen HR: data karyawan, absensi, cuti, payroll, performance review.</p>',
            'ticket_prefix' => 'HRMS',
            'color' => '#8B5CF6',
            'sdlc_phase' => 'testing',
            'project_request_id' => $req2->id,
            'start_date' => '2026-04-20',
            'end_date' => '2026-09-30',
        ]);

        $req2->update(['project_id' => $project2->id]);

        $s2Backlog    = $project2->ticketStatuses()->create(['name' => 'Backlog', 'color' => '#6B7280', 'sort_order' => 0]);
        $s2Todo       = $project2->ticketStatuses()->create(['name' => 'To Do', 'color' => '#F59E0B', 'sort_order' => 1]);
        $s2InProgress = $project2->ticketStatuses()->create(['name' => 'In Progress', 'color' => '#3B82F6', 'sort_order' => 2]);
        $s2Review     = $project2->ticketStatuses()->create(['name' => 'Review', 'color' => '#8B5CF6', 'sort_order' => 3]);
        $s2Done       = $project2->ticketStatuses()->create(['name' => 'Done', 'color' => '#10B981', 'sort_order' => 4, 'is_completed' => true]);
        $s2Testing    = $project2->ticketStatuses()->create(['name' => 'QA Testing', 'color' => '#EC4899', 'sort_order' => 5]);

        $project2->members()->syncWithoutDetaching([$manager->id, $analyst->id, $cto->id, $programmer->id, $qa->id]);

        $epic2a = Epic::create(['project_id' => $project2->id, 'name' => 'Employee Management', 'description' => 'CRUD employee data, profile, documents', 'start_date' => '2026-04-20', 'end_date' => '2026-05-31', 'sort_order' => 0]);
        $epic2b = Epic::create(['project_id' => $project2->id, 'name' => 'Attendance & Leave', 'description' => 'Clock in/out, leave request/approval', 'start_date' => '2026-06-01', 'end_date' => '2026-07-31', 'sort_order' => 1]);
        $epic2c = Epic::create(['project_id' => $project2->id, 'name' => 'Payroll', 'description' => 'Salary calculation, deductions, payslip', 'start_date' => '2026-08-01', 'end_date' => '2026-09-15', 'sort_order' => 2]);

        // Employee Management — all done
        $h1 = Ticket::create(['project_id' => $project2->id, 'ticket_status_id' => $s2Done->id, 'priority_id' => $priorityHigh->id, 'epic_id' => $epic2a->id, 'name' => 'Employee database schema & migration', 'description' => '<p>Tabel employees, departments, positions, employee_documents.</p>', 'start_date' => '2026-04-20', 'due_date' => '2026-04-25', 'created_by' => $manager->id]);
        $h1->assignees()->sync([$programmer->id]);

        $h2 = Ticket::create(['project_id' => $project2->id, 'ticket_status_id' => $s2Done->id, 'priority_id' => $priorityHigh->id, 'epic_id' => $epic2a->id, 'name' => 'CRUD employee data + photo upload', 'description' => '<p>Filament resource untuk manage employee data.</p>', 'start_date' => '2026-04-25', 'due_date' => '2026-05-10', 'created_by' => $manager->id]);
        $h2->assignees()->sync([$programmer->id]);

        $h3 = Ticket::create(['project_id' => $project2->id, 'ticket_status_id' => $s2Done->id, 'priority_id' => $priorityMedium->id, 'epic_id' => $epic2a->id, 'name' => 'Department & position management', 'description' => '<p>CRUD departments dan positions dengan hierarchy.</p>', 'start_date' => '2026-05-05', 'due_date' => '2026-05-15', 'created_by' => $manager->id]);
        $h3->assignees()->sync([$programmer->id]);

        // Attendance & Leave — in QA testing
        $h4 = Ticket::create(['project_id' => $project2->id, 'ticket_status_id' => $s2Testing->id, 'priority_id' => $priorityCritical->id, 'epic_id' => $epic2b->id, 'name' => 'Clock in/out dengan geolocation', 'description' => '<p>Karyawan bisa clock in/out dari mobile. Validasi radius lokasi kantor.</p>', 'start_date' => '2026-06-01', 'due_date' => '2026-06-20', 'created_by' => $manager->id]);
        $h4->assignees()->sync([$programmer->id, $qa->id]);

        $h5 = Ticket::create(['project_id' => $project2->id, 'ticket_status_id' => $s2Testing->id, 'priority_id' => $priorityHigh->id, 'epic_id' => $epic2b->id, 'name' => 'Leave request & approval workflow', 'description' => '<p>Employee submit cuti → Manager approve/reject → HR confirm. Track sisa cuti otomatis.</p>', 'start_date' => '2026-06-15', 'due_date' => '2026-07-10', 'created_by' => $manager->id]);
        $h5->assignees()->sync([$programmer->id, $qa->id]);

        $h6 = Ticket::create(['project_id' => $project2->id, 'ticket_status_id' => $s2Testing->id, 'priority_id' => $priorityMedium->id, 'epic_id' => $epic2b->id, 'name' => 'Attendance report & dashboard', 'description' => '<p>Dashboard attendance: daily summary, monthly report, late/absent statistics.</p>', 'start_date' => '2026-07-01', 'due_date' => '2026-07-20', 'created_by' => $manager->id]);
        $h6->assignees()->sync([$programmer->id, $qa->id]);

        // Payroll — in progress
        $h7 = Ticket::create(['project_id' => $project2->id, 'ticket_status_id' => $s2InProgress->id, 'priority_id' => $priorityCritical->id, 'epic_id' => $epic2c->id, 'name' => 'Payroll calculation engine', 'description' => '<p>Hitung gaji: basic salary + allowances - deductions (BPJS, PPh21, absence). Support prorate.</p>', 'start_date' => '2026-08-01', 'due_date' => '2026-08-25', 'created_by' => $manager->id]);
        $h7->assignees()->sync([$programmer->id]);

        $h8 = Ticket::create(['project_id' => $project2->id, 'ticket_status_id' => $s2Todo->id, 'priority_id' => $priorityHigh->id, 'epic_id' => $epic2c->id, 'name' => 'Generate payslip PDF', 'description' => '<p>Generate payslip dalam format PDF. Bisa bulk generate untuk semua karyawan per bulan.</p>', 'start_date' => '2026-08-20', 'due_date' => '2026-09-05', 'created_by' => $manager->id]);
        $h8->assignees()->sync([$programmer->id]);

        // Comments
        TicketComment::withoutEvents(fn () => TicketComment::create(['ticket_id' => $h4->id, 'user_id' => $qa->id, 'comment' => '<p>Bug found: geolocation accuracy kurang akurat di dalam gedung. Radius 100m terlalu kecil, suggest 200m.</p>']));
        TicketComment::withoutEvents(fn () => TicketComment::create(['ticket_id' => $h4->id, 'user_id' => $programmer->id, 'comment' => '<p>Sudah adjust radius ke 200m. Juga tambah fallback pakai WiFi SSID kalau GPS tidak akurat.</p>']));
        TicketComment::withoutEvents(fn () => TicketComment::create(['ticket_id' => $h5->id, 'user_id' => $qa->id, 'comment' => '<p>Edge case: karyawan apply cuti di tanggal merah. Seharusnya tidak mengurangi saldo cuti. Currently still deducting.</p>']));
        TicketComment::withoutEvents(fn () => TicketComment::create(['ticket_id' => $h7->id, 'user_id' => $programmer->id, 'comment' => '<p>PPh21 calculation sudah sesuai aturan 2026. Perlu review dari accounting team sebelum go live.</p>']));

        // Links
        TicketLink::create(['ticket_id' => $h7->id, 'title' => 'PPh21 Calculation Reference', 'url' => 'https://pajak.go.id/pph21', 'added_by' => $programmer->id]);
        TicketLink::create(['ticket_id' => $h4->id, 'title' => 'Geolocation API Docs', 'url' => 'https://developer.mozilla.org/en-US/docs/Web/API/Geolocation_API', 'added_by' => $programmer->id]);

        // Request history
        ProjectRequestHistory::create(['project_request_id' => $req2->id, 'user_id' => $manager->id, 'action' => 'created', 'from_status' => null, 'to_status' => 'draft']);
        ProjectRequestHistory::create(['project_request_id' => $req2->id, 'user_id' => $manager->id, 'action' => 'assigned_analyst', 'from_status' => 'draft', 'to_status' => 'pending_analysis']);
        ProjectRequestHistory::create(['project_request_id' => $req2->id, 'user_id' => $analyst->id, 'action' => 'analysis_submitted', 'from_status' => 'pending_analysis', 'to_status' => 'analysis_submitted']);
        ProjectRequestHistory::create(['project_request_id' => $req2->id, 'user_id' => $manager->id, 'action' => 'recommended', 'from_status' => 'analysis_submitted', 'to_status' => 'recommended_approve']);
        ProjectRequestHistory::create(['project_request_id' => $req2->id, 'user_id' => $cto->id, 'action' => 'approved', 'from_status' => 'recommended_approve', 'to_status' => 'approved']);

        $this->command->info('✓ Project 2: HR Management System (Testing phase, 8 tickets)');

        // ════════════════════════════════════════════════════════
        // PROJECT 3: Internal Knowledge Base — SDLC phase: Planning
        // Request baru saja di-approve, project baru dibuat
        // ════════════════════════════════════════════════════════

        $req3 = ProjectRequest::create([
            'title' => 'Internal Knowledge Base',
            'description' => '<p>Wiki internal perusahaan untuk dokumentasi proses, SOP, troubleshooting guide, dan onboarding materials.</p>',
            'business_justification' => '<p>Knowledge saat ini tersebar di Google Docs, Notion, dan kepala masing-masing orang. Saat karyawan resign, knowledge hilang. Knowledge base terpusat akan mempercepat onboarding dari 2 minggu menjadi 3 hari.</p>',
            'priority' => 'medium',
            'requested_deadline' => '2027-03-31',
            'status' => 'approved',
            'requested_by' => $manager->id,
            'analyst_id' => $analyst->id,
            'requirement_analysis' => '<p><strong>Features:</strong></p><ul><li>Rich text editor dengan markdown support</li><li>Hierarchical categories & tags</li><li>Full-text search</li><li>Version history per article</li><li>Role-based access (public, internal, confidential)</li><li>Comment & feedback system</li></ul>',
            'feasibility_study' => '<p>Bisa dibangun dengan Laravel + Filament. Search pakai Laravel Scout + Meilisearch. Estimated effort: 2 programmer, 3 bulan. Low risk karena tidak ada integrasi external yang complex.</p>',
            'technical_notes' => '<p>Stack: Laravel + Filament + Meilisearch. Editor: TipTap (rich text). Storage: local disk untuk attachments. Consider SSO integration with existing Google Workspace.</p>',
            'analysis_submitted_at' => '2026-07-10 15:00:00',
            'manager_recommendation' => 'approve',
            'manager_recommendation_reason' => '<p>Nice to have tapi penting untuk long-term knowledge retention. Timeline tidak mendesak, bisa dikerjakan paralel dengan project lain. Recommend approve dengan timeline yang flexible.</p>',
            'recommended_by' => $manager->id,
            'recommended_at' => '2026-07-12 10:00:00',
            'cto_decision' => 'approve',
            'cto_decision_reason' => '<p>Approved. Start dengan MVP: article CRUD + search + categories. Fitur advanced (versioning, SSO) bisa ditambah later. Assign 1 programmer dulu, scale up kalau perlu.</p>',
            'decided_by' => $cto->id,
            'decided_at' => '2026-07-15 09:00:00',
        ]);

        $project3 = Project::create([
            'name' => 'Internal Knowledge Base',
            'description' => '<p>Wiki internal perusahaan untuk dokumentasi proses, SOP, troubleshooting guide, dan onboarding materials.</p>',
            'ticket_prefix' => 'WIKI',
            'color' => '#10B981',
            'sdlc_phase' => 'planning',
            'project_request_id' => $req3->id,
            'start_date' => '2026-08-01',
            'end_date' => '2027-03-31',
        ]);

        $req3->update(['project_id' => $project3->id]);

        $s3Backlog    = $project3->ticketStatuses()->create(['name' => 'Backlog', 'color' => '#6B7280', 'sort_order' => 0]);
        $s3Todo       = $project3->ticketStatuses()->create(['name' => 'To Do', 'color' => '#F59E0B', 'sort_order' => 1]);
        $s3InProgress = $project3->ticketStatuses()->create(['name' => 'In Progress', 'color' => '#3B82F6', 'sort_order' => 2]);
        $s3Review     = $project3->ticketStatuses()->create(['name' => 'Review', 'color' => '#8B5CF6', 'sort_order' => 3]);
        $s3Done       = $project3->ticketStatuses()->create(['name' => 'Done', 'color' => '#10B981', 'sort_order' => 4, 'is_completed' => true]);

        $project3->members()->syncWithoutDetaching([$manager->id, $analyst->id, $cto->id, $programmer->id]);

        $epic3a = Epic::create(['project_id' => $project3->id, 'name' => 'Core Wiki Engine', 'description' => 'Article CRUD, categories, tags, search', 'start_date' => '2026-08-01', 'end_date' => '2026-10-31', 'sort_order' => 0]);
        $epic3b = Epic::create(['project_id' => $project3->id, 'name' => 'Access Control & Collaboration', 'description' => 'Roles, permissions, comments, versioning', 'start_date' => '2026-11-01', 'end_date' => '2027-01-31', 'sort_order' => 1]);

        // All tickets in backlog — project masih planning
        Ticket::create(['project_id' => $project3->id, 'ticket_status_id' => $s3Backlog->id, 'priority_id' => $priorityHigh->id, 'epic_id' => $epic3a->id, 'name' => 'Database schema untuk articles, categories, tags', 'description' => '<p>Design database schema. Tabel: articles, categories, tags, article_tags pivot.</p>', 'start_date' => '2026-08-01', 'due_date' => '2026-08-05', 'created_by' => $analyst->id]);
        Ticket::create(['project_id' => $project3->id, 'ticket_status_id' => $s3Backlog->id, 'priority_id' => $priorityHigh->id, 'epic_id' => $epic3a->id, 'name' => 'Article CRUD dengan TipTap editor', 'description' => '<p>Create, edit, delete articles. Rich text editor dengan support embed images, code blocks, tables.</p>', 'start_date' => '2026-08-05', 'due_date' => '2026-08-25', 'created_by' => $analyst->id]);
        Ticket::create(['project_id' => $project3->id, 'ticket_status_id' => $s3Backlog->id, 'priority_id' => $priorityMedium->id, 'epic_id' => $epic3a->id, 'name' => 'Category hierarchy management', 'description' => '<p>Nested categories dengan drag-and-drop reordering.</p>', 'start_date' => '2026-08-20', 'due_date' => '2026-09-05', 'created_by' => $analyst->id]);
        Ticket::create(['project_id' => $project3->id, 'ticket_status_id' => $s3Backlog->id, 'priority_id' => $priorityHigh->id, 'epic_id' => $epic3a->id, 'name' => 'Full-text search dengan Meilisearch', 'description' => '<p>Search articles by title, content, tags. Highlight matched text. Instant search suggestions.</p>', 'start_date' => '2026-09-01', 'due_date' => '2026-09-20', 'created_by' => $analyst->id]);
        Ticket::create(['project_id' => $project3->id, 'ticket_status_id' => $s3Backlog->id, 'priority_id' => $priorityMedium->id, 'epic_id' => $epic3b->id, 'name' => 'Role-based article access control', 'description' => '<p>Article visibility levels: public, internal, confidential. Map ke user roles.</p>', 'start_date' => '2026-11-01', 'due_date' => '2026-11-20', 'created_by' => $analyst->id]);
        Ticket::create(['project_id' => $project3->id, 'ticket_status_id' => $s3Backlog->id, 'priority_id' => $priorityLow->id, 'epic_id' => $epic3b->id, 'name' => 'Article version history & diff viewer', 'description' => '<p>Track setiap perubahan article. Bisa lihat diff antara versi dan restore ke versi sebelumnya.</p>', 'start_date' => '2026-12-01', 'due_date' => '2027-01-15', 'created_by' => $analyst->id]);

        // Request history
        ProjectRequestHistory::create(['project_request_id' => $req3->id, 'user_id' => $manager->id, 'action' => 'created', 'from_status' => null, 'to_status' => 'draft']);
        ProjectRequestHistory::create(['project_request_id' => $req3->id, 'user_id' => $manager->id, 'action' => 'assigned_analyst', 'from_status' => 'draft', 'to_status' => 'pending_analysis']);
        ProjectRequestHistory::create(['project_request_id' => $req3->id, 'user_id' => $analyst->id, 'action' => 'analysis_submitted', 'from_status' => 'pending_analysis', 'to_status' => 'analysis_submitted']);
        ProjectRequestHistory::create(['project_request_id' => $req3->id, 'user_id' => $manager->id, 'action' => 'recommended', 'from_status' => 'analysis_submitted', 'to_status' => 'recommended_approve']);
        ProjectRequestHistory::create(['project_request_id' => $req3->id, 'user_id' => $cto->id, 'action' => 'approved', 'from_status' => 'recommended_approve', 'to_status' => 'approved']);

        $this->command->info('✓ Project 3: Internal Knowledge Base (Planning phase, 6 tickets)');

        // ════════════════════════════════════════════════════════
        // BONUS: Request yang masih pending (belum approved)
        // ════════════════════════════════════════════════════════

        // Request 4: Pending recommendation (analysis done, waiting manager)
        ProjectRequest::create([
            'title' => 'Mobile App untuk Sales Team',
            'description' => '<p>Aplikasi mobile (Flutter) untuk sales team: track visit, input order, lihat target & achievement real-time.</p>',
            'business_justification' => '<p>Sales team saat ini report via WhatsApp. Tidak ada tracking visit yang reliable. Mobile app akan meningkatkan akuntabilitas dan visibility management terhadap aktivitas sales.</p>',
            'priority' => 'high',
            'requested_deadline' => '2027-06-30',
            'status' => 'analysis_submitted',
            'requested_by' => $manager->id,
            'analyst_id' => $analyst->id,
            'requirement_analysis' => '<p><strong>Features:</strong></p><ul><li>GPS-based visit tracking</li><li>Customer database + visit history</li><li>Order input + product catalog</li><li>Dashboard: target vs achievement</li><li>Offline mode support</li></ul>',
            'feasibility_study' => '<p>Perlu hire Flutter developer atau outsource. Backend bisa pakai existing Laravel API. Estimated: 4-5 bulan. Risk: tim belum ada pengalaman Flutter.</p>',
            'technical_notes' => '<p>Frontend: Flutter. Backend: Laravel API. Maps: Google Maps API. Offline: SQLite + sync mechanism.</p>',
            'analysis_submitted_at' => '2026-07-16 14:00:00',
        ]);

        ProjectRequestHistory::create(['project_request_id' => 4, 'user_id' => $manager->id, 'action' => 'created', 'from_status' => null, 'to_status' => 'draft']);
        ProjectRequestHistory::create(['project_request_id' => 4, 'user_id' => $manager->id, 'action' => 'assigned_analyst', 'from_status' => 'draft', 'to_status' => 'pending_analysis']);
        ProjectRequestHistory::create(['project_request_id' => 4, 'user_id' => $analyst->id, 'action' => 'analysis_submitted', 'from_status' => 'pending_analysis', 'to_status' => 'analysis_submitted']);

        $this->command->info('✓ Request 4: Mobile App Sales Team (Waiting recommendation)');

        // Request 5: Draft (baru dibuat, belum assign analyst)
        ProjectRequest::create([
            'title' => 'Customer Support Ticketing System',
            'description' => '<p>Sistem ticketing untuk customer support. Customer bisa submit ticket via web/email, support team manage dan respond.</p>',
            'business_justification' => '<p>Volume customer inquiry meningkat 300% tahun ini. Email tidak scalable. Butuh proper ticketing system dengan SLA tracking dan knowledge base integration.</p>',
            'priority' => 'medium',
            'requested_deadline' => '2027-09-30',
            'status' => 'draft',
            'requested_by' => $manager->id,
        ]);

        ProjectRequestHistory::create(['project_request_id' => 5, 'user_id' => $manager->id, 'action' => 'created', 'from_status' => null, 'to_status' => 'draft']);

        $this->command->info('✓ Request 5: Customer Support Ticketing (Draft)');

        $this->command->info('');
        $this->command->info('══════════════════════════════════════');
        $this->command->info('Demo data seeding complete!');
        $this->command->info('  3 Projects (Implementation, Testing, Planning)');
        $this->command->info('  5 Project Requests (3 approved, 1 pending, 1 draft)');
        $this->command->info('  23 Tickets total with comments & links');
        $this->command->info('══════════════════════════════════════');
    }
}
