<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Doctor::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('name', 'like', "%{$s}%")
                  ->orWhere('registration_number', 'like', "%{$s}%")
                  ->orWhere('clinic_hospital_name', 'like', "%{$s}%");
        }

        $doctors = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => DoctorResource::collection($doctors)->response()->getData(true),
        ]);
    }

    public function show(Doctor $doctor): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new DoctorResource($doctor),
        ]);
    }

    public function store(CreateDoctorRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $request->user()->organization_id;

        $doctor = Doctor::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Doctor profile added successfully',
            'data' => new DoctorResource($doctor),
        ], 201);
    }
}
