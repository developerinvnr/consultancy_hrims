<?php
// app/Http/Controllers/CommunicationControlController.php

namespace App\Http\Controllers;

use App\Models\CommunicationControl;
use App\Services\CommunicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommunicationControlController extends Controller
{
	protected $communicationService;

	public function __construct(CommunicationService $communicationService)
	{
		$this->communicationService = $communicationService;
		$this->middleware(['auth']);
	}

	public function index()
	{
		// Check if user has admin or hr_admin role
		$user = Auth::user();
		if (!$user->hasRole('hr_admin') && !$user->hasRole('Admin')) {
			abort(403, 'Unauthorized access.');
		}

		$controls = CommunicationControl::orderBy('created_at', 'desc')->get();

		return view('communication_controls.index', compact('controls'));
	}

	public function store(Request $request)
	{
		// Check authorization
		$user = Auth::user();
		if (!$user->hasRole('hr_admin') && !$user->hasRole('Admin')) {
			return response()->json(['error' => 'Unauthorized'], 403);
		}

		$request->validate([
			'control_key ' => 'required|string|max:100|unique:communication_controls,control_key ',
			'description' => 'nullable|string|max:255',
		]);

		$control = CommunicationControl::create([
			'control_key ' => $request->control_key,
			'description' => $request->description,
			'is_active' => true,
			'created_by' => Auth::id(),
			'updated_by' => Auth::id(),
		]);

		// Clear cache for this key
		$this->communicationService->clearCache($request->control_key);

		return response()->json([
			'success' => true,
			'message' => 'Control created successfully!',
			'control' => $control
		]);
	}

	public function toggle(CommunicationControl $communicationControl, Request $request)
	{
		// Check authorization
		$user = Auth::user();
		if (!$user->hasRole('hr_admin') && !$user->hasRole('Admin')) {
			return response()->json(['error' => 'Unauthorized'], 403);
		}

		$newStatus = !$communicationControl->is_active;

		$communicationControl->update([
			'is_active' => $newStatus,
			'updated_by' => Auth::id(),
		]);

		// Clear cache for this control
		$this->communicationService->clearCache($communicationControl->control_key);

		// Log the change
		\Log::info('Communication control toggled', [
			'control_key ' => $communicationControl->control_key,
			'status' => $newStatus ? 'active' : 'inactive',
			'user' => Auth::user()->email,
		]);

		return response()->json([
			'success' => true,
			'is_active' => $newStatus,
			'message' => 'Control ' . ($newStatus ? 'activated' : 'deactivated') . ' successfully!'
		]);
	}

	/**
	 * Test communication control
	 */
	public function test(Request $request)
	{
		// Check authorization
		$user = Auth::user();
		if (!$user->hasRole('hr_admin') && !$user->hasRole('Admin')) {
			return response()->json(['error' => 'Unauthorized'], 403);
		}

		$request->validate([
			'control_key' => 'required|string|exists:communication_controls,control_key ',
			'email' => 'required|email',
		]);

		try {
			$isEnabled = $this->communicationService->isEnabled($request->control_key);

			if (!$isEnabled) {
				return response()->json([
					'success' => false,
					'message' => 'This communication control is currently disabled. Enable it first to send test email.'
				]);
			}

			// Send test email
			\Mail::raw('Test email for communication control: ' . $request->control_key, function ($message) use ($request) {
				$message->to($request->email)
					->subject('Communication Control Test - ' . $request->control_key);
			});

			return response()->json([
				'success' => true,
				'message' => 'Test email sent successfully!'
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to send test email: ' . $e->getMessage()
			], 500);
		}
	}

	/**
	 * Get control statistics
	 */
	public function stats()
	{
		// Check authorization
		$user = Auth::user();
		if (!$user->hasRole('hr_admin') && !$user->hasRole('Admin')) {
			return response()->json(['error' => 'Unauthorized'], 403);
		}

		$controls = CommunicationControl::all();
		$activeCount = $controls->where('is_active', true)->count();
		$inactiveCount = $controls->where('is_active', false)->count();

		return response()->json([
			'success' => true,
			'data' => [
				'total' => $controls->count(),
				'active' => $activeCount,
				'inactive' => $inactiveCount,
				'active_percentage' => $controls->count() > 0 ? round(($activeCount / $controls->count()) * 100, 2) : 0,
				'controls' => $controls->map(function ($control) {
					return [
						'control_key ' => $control->control_key,
						'is_active' => $control->is_active,
						'description' => $control->description,
					];
				})
			]
		]);
	}
}
