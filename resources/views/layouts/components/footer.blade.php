@php
    $footerText = \App\Models\Setting::get('app_footer_text', '');
    // If no custom footer text, use default
    if (empty($footerText)) {
        $footerText = 'Copyright © <span id="year"></span> <a href="javascript:void(0);" class="text-dark fw-medium">pingXtechnology</a>. Designed with <span class="bi bi-heart-fill text-danger"></span> by <a href="https://pingxtechnology.com/" target="_blank"> <span class="fw-medium text-primary">pingXtechnology</span></a> All rights reserved';
    }
@endphp

			<footer class="footer mt-auto py-3 text-center">
				<div class="container">
					<span class="text-muted">
						{!! $footerText !!}
					</span>
				</div>
			</footer>