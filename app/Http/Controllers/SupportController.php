<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Setting;
use Illuminate\View\View;

class SupportController extends Controller
{
    /**
     * Display the support & help page with FAQs.
     */
    public function index(): View
    {
        $faqs = Faq::active()->ordered()->get();
        $settings = Setting::getAllCached();
        $supportEmail = $settings['app_email'] ?? config('mail.from.address', 'support@example.com');
        
        // Group FAQs by category
        $faqsByCategory = $faqs->groupBy('category');
        
        // Get all unique categories
        $categories = $faqs->pluck('category')->unique()->values();
        
        return view('shared.support.index', compact('faqs', 'faqsByCategory', 'categories', 'supportEmail'));
    }
}
