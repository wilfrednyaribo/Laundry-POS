<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // Show the list of services and the create form.
    // Soft-deleted services are automatically excluded by Eloquent.
    public function index()
    {
        $services = Service::latest()->get();
        return view('services.index', compact('services'));
    }

    // Store the new service
    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit'  => 'required|in:kg,piece',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
        ]);

        $imageName = null;

        // Handle Image Upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            // Move image to public/images folder
            $image->move(public_path('images/services'), $imageName);
        }

        Service::create([
            'name'  => $request->name,
            'price' => $request->price,
            'unit'  => $request->unit,
            'image' => $imageName,
        ]);

        return redirect()->route('services.index')->with('success', 'Service created successfully.');
    }

    // Update the specified service (used by the Edit modal)
    public function update(Request $request, Service $service)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'unit'         => 'required|in:kg,piece',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'remove_image' => 'sometimes|in:0,1',
        ]);

        $imageName = $service->image;

        // New image uploaded -> replace
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/services'), $imageName);

            // Delete old image file
            if ($service->image && file_exists(public_path('images/services/' . $service->image))) {
                unlink(public_path('images/services/' . $service->image));
            }
        }
        // User clicked the X to remove the existing image
        elseif ($request->input('remove_image') === '1' && $service->image) {
            if (file_exists(public_path('images/services/' . $service->image))) {
                unlink(public_path('images/services/' . $service->image));
            }
            $imageName = null;
        }

        $service->update([
            'name'  => $request->name,
            'price' => $request->price,
            'unit'  => $request->unit,
            'image' => $imageName,
        ]);

        return back()->with('success', 'Service updated successfully.');
    }

    // Delete Service (soft delete — does NOT affect existing orders)
    public function destroy(Service $service)
    {
        // Soft delete: sets deleted_at, row stays in the DB so order_items FK is intact.
        // Existing orders continue to resolve to this service via withTrashed() when needed.
        $service->delete();

        return back()->with('success', 'Service deleted.');
    }
}
