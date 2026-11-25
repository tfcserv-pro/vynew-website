<?php
/**
 * Template Name: Zucora Request Form
 */

get_header();

$is_zucora = is_page('zucora-referral-request');
$request_source = $is_zucora ? 'zucora' : 'vynew';
?>

<style>
.zucora-form-wrapper {
    max-width: 800px;
    margin: 40px auto 90px;
    padding: 20px;
}

.zucora-form-wrapper h3 {
    margin-top: 30px;
    margin-bottom: 22px;
    font-size: 30px;
    font-weight: 700;
}

.zucora-form-wrapper label {
    display: inline-block;
    margin-bottom: 8px;
    font-weight: 600;
}

.zucora-form-wrapper input,
.zucora-form-wrapper select,
.zucora-form-wrapper textarea {
    width: 100%;
    border: 1px solid #d9dee5;
    padding: 14px 16px;
    font-size: 15px;
    border-radius: 2px;
    box-sizing: border-box;
}

.zucora-submit-wrap {
    text-align: center;
    margin-top: 40px;
}

.zucora-upload-btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;

    background: transparent !important;
    color: #5f6b7a !important;
    border: 1px solid #5f6b7a !important;

    padding: 10px 20px !important;
    min-width: 150px !important;

    font-size: 13px !important;
    font-weight: 700 !important;
    letter-spacing: 0.4px !important;

    border-radius: 2px !important;
    cursor: pointer !important;
    box-shadow: none !important;
    text-decoration: none !important;
    appearance: none !important;
}

.zucora-upload-btn:hover {
    background: #5f6b7a !important;
    color: #ffffff !important;
}

.zucora-submit-btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;

    background: #5f6b7a !important;
    color: #ffffff !important;
    border: none !important;

    padding: 12px 24px !important;
    min-width: 165px !important;

    font-size: 13px !important;
    font-weight: 700 !important;
    letter-spacing: 0.4px !important;

    border-radius: 2px !important;
    cursor: pointer !important;
}

.zucora-submit-btn:hover {
    background: #111111 !important;
}

.zucora-file-text {
    margin-top: 12px;
    color: #6b7280;
    font-size: 14px;
}

#photoPreview {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin: 15px 0 25px;
}

.zucora-preview-item {
    width: 130px;
    border: 1px solid #d9dee5;
    padding: 8px;
    font-size: 12px;
    box-sizing: border-box;
}

.zucora-preview-item img {
    width: 100%;
    height: 90px;
    object-fit: cover;
}

.zucora-remove-file {
    margin-top: 8px;
    width: 100%;
    background: #eeeeee;
    border: none;
    padding: 6px;
    cursor: pointer;
}

.zucora-submit-btn:disabled {
    opacity: 0.7 !important;
    cursor: not-allowed !important;
}

.zucora-upload-field {
    margin-top: 20px;
}

.zucora-upload-row {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-top: 8px;
}

.zucora-upload-note {
    font-size: 13px;
    color: #7b8794;
    line-height: 1.4;
}

.zucora-file-text {
    margin-top: 12px;
    color: #6b7280;
    font-size: 14px;
}

.zucora-error-message {
    background: #fff3f3;
    border: 1px solid #f1b5b5;
    color: #9f1d1d;
    padding: 14px 18px;
    margin-bottom: 25px;
    border-radius: 3px;
    font-size: 14px;
    line-height: 1.5;
}
</style>

<div class="zucora-form-wrapper">

    <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" id="zucoraRequestForm">
        <input type="hidden" name="action" value="zucora_referral_request">
        <?php wp_nonce_field('zucora_referral_request_nonce', 'zucora_nonce'); ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'failed') : ?>
            <div class="zucora-error-message">
                Sorry, your request could not be submitted at this time. Please try again, or contact us directly at service@vynew.com.
            </div>
        <?php endif; ?>
        
        <h3>Customer Information</h3>

        <p>
            <label>Full Name</label>
            <input type="text" name="full_name" id="full_name" required placeholder="John Smith">
        </p>

        <p>
            <label>Phone Number</label>
            <input type="tel" name="phone" id="phone" required placeholder="555-123-4567" maxlength="12">
        </p>

        <p>
            <label>Email</label>
            <input type="email" name="email" required placeholder="name@example.com">
        </p>

        <p>
            <label>Postal Code</label>
            <input
                type="text"
                name="postal_code"
                id="postal_code"
                required
                placeholder="M1C 5K4"
                maxlength="7"
                pattern="[A-Za-z][0-9][A-Za-z][ ]?[0-9][A-Za-z][0-9]"
                title="Please enter a valid Canadian postal code, for example M1C 5K4"
            >
        </p>

        <hr>

        <h3>Furniture Information</h3>

        <p>
            <label>Furniture Type</label>
            <input
                type="text"
                name="furniture_type"
                required
                placeholder="Example: Sofa, Sectional, Recliner, Dining Chair"
            >
        </p>

        <p>
            <label>Issue Type</label>
            <input
                type="text"
                name="issue_type"
                required
                placeholder="Example: Wood damage, Fabric tear, Leather issue, Mechanism problem"
            >
        </p>

        <p>
            <label>Please Describe Your Request</label>
            <textarea name="description" rows="6" placeholder="Example: I would like to repair a tear on my sofa cushion and receive a quote for the service."></textarea>
        </p>

        <div class="zucora-upload-field">
            <label>Upload Photos <span style="font-weight:400;color:#7b8794;">(Optional)</span></label>

            <div class="zucora-upload-row">
                <button
                    type="button"
                    id="uploadPhotosBtn"
                    class="zucora-upload-btn"
                    onclick="document.getElementById('photos').click();"
                >
                    CHOOSE FILES
                </button>

                <span class="zucora-upload-note">
                    JPG, PNG, PDF · Max 10MB
                </span>
            </div>

            <input
                type="file"
                name="photos[]"
                id="photos"
                multiple
                accept=".jpg,.jpeg,.png,.pdf"
                style="display:none;"
            >

            <div id="photoFileText" class="zucora-file-text">
                No photos uploaded yet
            </div>
        </div>

        <div id="photoPreview"></div>

        <input type="hidden" name="request_source" value="<?php echo esc_attr($request_source); ?>">

        <?php if (vynew_turnstile_enabled()) : ?>
        <?php $turnstile_site_key = defined('VYNEW_TURNSTILE_SITE_KEY') ? VYNEW_TURNSTILE_SITE_KEY : ''; ?>
        <?php if (is_string($turnstile_site_key) && $turnstile_site_key !== '') : ?>
            <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($turnstile_site_key); ?>" data-action="public_service_request"></div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <?php else : ?>
            <p role="alert">Verification is temporarily unavailable. Please contact service@vynew.com.</p>
        <?php endif; ?>
        <?php endif; ?>

        <p>
            <div class="zucora-submit-wrap">
                <button type="submit" class="zucora-submit-btn">
                    SUBMIT REQUEST
                </button>
            </div>
        </p>

    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fullNameInput = document.getElementById('full_name');
    const phoneInput = document.getElementById('phone');
    const postalInput = document.getElementById('postal_code');
    const photoInput = document.getElementById('photos');
    const preview = document.getElementById('photoPreview');
    const photoFileText = document.getElementById('photoFileText');

    const form = document.getElementById('zucoraRequestForm');
    const submitBtn = document.querySelector('.zucora-submit-btn');

    form.addEventListener('submit', function (event) {
        const token = form.querySelector('[name="cf-turnstile-response"]');
        if (<?php echo vynew_turnstile_enabled() ? 'true' : 'false'; ?> && (!token || !token.value)) {
            event.preventDefault();
            alert('Please complete verification before submitting.');
            return;
        }
        submitBtn.disabled = true;
        submitBtn.innerText = 'SUBMITTING...';
        submitBtn.style.opacity = '0.7';
        submitBtn.style.cursor = 'not-allowed';
    });

    let selectedFiles = [];

    fullNameInput.addEventListener('input', function () {

        this.value = this.value
            .toLowerCase()
            .replace(/\b\w/g, function(char) {
                return char.toUpperCase();
            });

    });

    phoneInput.addEventListener('input', function () {
        let digits = this.value.replace(/\D/g, '').substring(0, 10);

        if (digits.length > 6) {
            this.value = digits.substring(0, 3) + '-' + digits.substring(3, 6) + '-' + digits.substring(6);
        } else if (digits.length > 3) {
            this.value = digits.substring(0, 3) + '-' + digits.substring(3);
        } else {
            this.value = digits;
        }
    });

    postalInput.addEventListener('input', function () {
        let raw = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        let result = '';

        const pattern = ['L', 'N', 'L', 'N', 'L', 'N'];

        for (let i = 0; i < raw.length && result.length < 6; i++) {
            let char = raw[i];
            let expected = pattern[result.length];

            if (expected === 'L' && /[A-Z]/.test(char)) {
                result += char;
            }

            if (expected === 'N' && /[0-9]/.test(char)) {
                result += char;
            }
        }

        if (result.length > 3) {
            this.value = result.substring(0, 3) + ' ' + result.substring(3);
        } else {
            this.value = result;
        }
    });

    photoInput.addEventListener('change', function () {
        selectedFiles = Array.from(this.files);
        renderPreview();
    });

    function renderPreview() {
        preview.innerHTML = '';

        const dataTransfer = new DataTransfer();

        selectedFiles.forEach(function (file, index) {
            dataTransfer.items.add(file);

            const item = document.createElement('div');
            item.className = 'zucora-preview-item';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                item.appendChild(img);
            } else {
                const fileLabel = document.createElement('div');
                fileLabel.innerText = file.name;
                item.appendChild(fileLabel);
            }

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'zucora-remove-file';
            removeBtn.innerText = 'Remove';

            removeBtn.addEventListener('click', function () {
                selectedFiles.splice(index, 1);
                renderPreview();
            });

            item.appendChild(removeBtn);
            preview.appendChild(item);
        });

        photoInput.files = dataTransfer.files;

        photoFileText.innerText = selectedFiles.length
            ? selectedFiles.length + ' file(s) selected'
            : 'No files selected';
    }
});
</script>

<?php get_footer(); ?>
