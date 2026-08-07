# Detailed Changes Document (`dev` vs `main`)

This document provides a line-by-line and file-by-file breakdown of all changes introduced on the **`dev`** branch relative to `main`.

---

## Summary of File Modifications

| File | Status | Category | Description |
|---|---|---|---|
| [`routes/web.php`](file:///c:/Users/anura/Downloads/AI%20Coding/Crewly360/routes/web.php) | **Modified** | Security | Demo login routes restricted to `local` / `testing` environment. |
| [`app/Http/Controllers/DealController.php`](file:///c:/Users/anura/Downloads/AI%20Coding/Crewly360/app/Http/Controllers/DealController.php) | **Modified** | Security / Data Integrity | Added `authorizeDeal` helper and `DB::transaction` wrapper for deal winning. |
| [`app/Http/Controllers/TaskProgressController.php`](file:///c:/Users/anura/Downloads/AI%20Coding/Crewly360/app/Http/Controllers/TaskProgressController.php) | **Modified** | Security | Added task owner/assignee authorization check on task progress updates. |
| [`app/Http/Controllers/EmployeeController.php`](file:///c:/Users/anura/Downloads/AI%20Coding/Crewly360/app/Http/Controllers/EmployeeController.php) | **Modified** | Privacy / Security | Restricted employee profile detail view to Admins, Managers, or self. |
| [`app/Http/Controllers/LeaveController.php`](file:///c:/Users/anura/Downloads/AI%20Coding/Crewly360/app/Http/Controllers/LeaveController.php) | **Modified** | Data Integrity | Used `firstOrCreate` for `LeaveBalance` before incrementing taken leave days. |
| [`composer.json`](file:///c:/Users/anura/Downloads/AI%20Coding/Crewly360/composer.json) | **Modified** | Configuration | Disabled `optimize-autoloader` for faster local dev autoloading. |
| [`tests/Feature/ExampleTest.php`](file:///c:/Users/anura/Downloads/AI%20Coding/Crewly360/tests/Feature/ExampleTest.php) | **Modified** | Testing | Corrected guest redirect status assertion. |
| [`tests/Feature/CrmTest.php`](file:///c:/Users/anura/Downloads/AI%20Coding/Crewly360/tests/Feature/CrmTest.php) | **New** | Testing | Automated test suite for CRM deal pipeline and cross-tenant access. |
| [`tests/Feature/TaskSecurityTest.php`](file:///c:/Users/anura/Downloads/AI%20Coding/Crewly360/tests/Feature/TaskSecurityTest.php) | **New** | Testing | Automated test suite for task progress update authorization. |
| [`tests/Feature/LeaveTest.php`](file:///c:/Users/anura/Downloads/AI%20Coding/Crewly360/tests/Feature/LeaveTest.php) | **New** | Testing | Automated test suite for manager leave approval & balance increment. |

---

## Detailed File-by-File Code Diffs

### 1. `routes/web.php`
**Goal**: Restrict demo login routes to local/testing environments to prevent production admin takeover.

```diff
-Route::post('/demo-login/{role}', DemoLoginController::class)->name('demo.login');
-
-// GET variant for local tooling/demos (e.g. scripted screenshots)
-if (app()->environment('local')) {
+// Demo login helper for local tooling and prototype preview
+if (app()->environment('local', 'testing')) {
+    Route::post('/demo-login/{role}', DemoLoginController::class)->name('demo.login');
     Route::get('/demo-login/{role}', DemoLoginController::class);
 }
```

---

### 2. `app/Http/Controllers/DealController.php`
**Goal**: Prevent cross-tenant IDOR access on deals and guarantee transactional safety when auto-generating delivery projects and onboarding tasks.

```diff
+use Illuminate\Support\Facades\DB;

 public function show(Deal $deal)
 {
+    $this->authorizeDeal($deal);
     $deal->load(['account', 'contact', 'owner', 'project.tasks.assignee']);

 public function updateStage(Request $request, Deal $deal)
 {
+    $this->authorizeDeal($deal);

 public function markWon(Request $request, Deal $deal)
 {
+    $this->authorizeDeal($deal);

-    $deal->update(['stage' => 'won']);
-    $project = Project::create([...]);
-    ...
+    DB::transaction(function () use ($deal, $request) {
+        $deal->update(['stage' => 'won']);
+        $project = Project::create([...]);
+        ...
+    });

+private function authorizeDeal(Deal $deal): void
+{
+    $user = auth()->user();
+    abort_unless(
+        $user && ($deal->organization_id === $user->organization_id),
+        403,
+        'Unauthorized access to deal.'
+    );
+}
```

---

### 3. `app/Http/Controllers/TaskProgressController.php`
**Goal**: Prevent BOLA vulnerabilities where users could post updates to arbitrary tasks.

```diff
 public function store(Request $request, Task $task)
 {
+    $user = $request->user();
+    abort_unless(
+        $user->isManager() || $task->assignee_id === $user->id || $task->created_by === $user->id,
+        403,
+        'You can only update your own tasks.'
+    );
```

---

### 4. `app/Http/Controllers/EmployeeController.php`
**Goal**: Prevent unauthorized employees from inspecting sensitive HR records of other employees.

```diff
-public function show(Employee $employee)
+public function show(Request $request, Employee $employee)
 {
+    $user = $request->user();
+    abort_unless(
+        $user->isManager() || $user->employee?->id === $employee->id,
+        403,
+        'You are only authorized to view your own employee details.'
+    );
```

---

### 5. `app/Http/Controllers/LeaveController.php`
**Goal**: Prevent queries from failing when approving leave for employees without pre-seeded leave balances.

```diff
 if ($data['decision'] === 'approved') {
-    LeaveBalance::where('employee_id', $leaveRequest->employee_id)...->increment('taken_days', ...);
+    $balance = LeaveBalance::firstOrCreate(
+        [
+            'employee_id' => $leaveRequest->employee_id,
+            'leave_type_id' => $leaveRequest->leave_type_id,
+            'year' => now()->year,
+        ],
+        [
+            'entitlement_days' => 20,
+            'taken_days' => 0,
+        ]
+    );
+    $balance->increment('taken_days', $leaveRequest->days);
 }
```

---

### 6. `tests/Feature/ExampleTest.php`
**Goal**: Ensure automated test checks guest login redirection.

```diff
-public function test_the_application_returns_a_successful_response(): void
+public function test_guests_are_redirected_to_login(): void
 {
     $response = $this->get('/');
-    $response->assertStatus(200);
+    $response->assertRedirect('/login');
 }
```

---

## New Automated Feature Tests Introduced

1. **`tests/Feature/CrmTest.php`**:
   - `test_user_can_create_and_update_deal_stage`: Verifies deal creation and AJAX stage transitions.
   - `test_user_cannot_access_or_modify_other_organization_deal`: Verifies 404 response on cross-tenant access.

2. **`tests/Feature/TaskSecurityTest.php`**:
   - `test_unauthorized_user_cannot_post_progress_update_to_other_users_task`: Verifies 403 status when posting to unassigned tasks.
   - `test_assignee_can_post_progress_update`: Verifies successful progress log creation for assigned users.

3. **`tests/Feature/LeaveTest.php`**:
   - `test_manager_approval_increments_leave_balance`: Verifies leave approval workflow and automatic balance initialization/increment.

---

## Verification & Status

All 30 unit & feature tests pass with 100% success rate:
```powershell
& "$env:USERPROFILE\scoop\shims\php.exe" artisan test
# Tests: 30 passed (75 assertions)
```
