<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate "Approval Kebutuhan GA" berdasarkan canAccessSectionApprovals() --
 * dept-scoped lewat department_section_approvers, bukan role === 'section'
 * literal, supaya staf rangkap jabatan (mis. GA yang juga approver Section
 * departemennya sendiri) tidak perlu ganti role utama.
 */
class CheckSectionApprovalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Akun Anda tidak aktif. Hubungi Administrator.');
        }

        if (!$user->canAccessSectionApprovals()) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
