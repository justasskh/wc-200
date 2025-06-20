<!-- WooCommerce Gifting Flow Step 2 - Updated 2025-06-18 09:46:30 UTC by justasskh -->
<div class="wcflow-modal wcflow-fullscreen" data-step="2">
    <header class="wcflow-header">
        <div class="wcflow-content-wrapper">
            <div class="wcflow-header-inner">
                <div class="wcflow-header-left">
                    <button class="wcflow-back-btn wcflow-btn-prev">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Atgal
                    </button>
                </div>
                <div class="wcflow-header-right">
                    <div class="wcflow-secure-checkout">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="16" r="1" fill="currentColor"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        Saugus atsiskaitymas
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="wcflow-modal-body">
        <div class="wcflow-loading-overlay" style="display: none;">
            <div class="wcflow-loader"></div>
        </div>
        
        <div class="wcflow-content-wrapper wcflow-form-wrapper">
            <h2 class="wcflow-main-title">Pristatymo informacija</h2>
            <p class="wcflow-form-subtitle">Tell us where to deliver your thoughtful gift</p>
            
            <?php if (!is_user_logged_in()) : ?>
            <div class="wcflow-login-prompt">
                <p>Turite pirkėjo paskyrą? <a href="#" id="wcflow-login-link">Prisijungti</a> for faster checkout</p>
            </div>
            <?php else : ?>
            <div class="wcflow-welcome-back">
                <p>Welcome back, <?php echo wp_get_current_user()->display_name; ?>!</p>
            </div>
            <?php endif; ?>

            <div class="wcflow-form-section">
                <h3 class="wcflow-form-section-title">Contact Information</h3>
                <div class="wcflow-form-row">
                    <div class="wcflow-form-group floating-label">
                        <input type="email" id="wcflow-customer-email" placeholder=" " value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" required autocomplete="email">
                        <label for="wcflow-customer-email">Pirkėjo el. pašto adresas *</label>
                        <span class="wcflow-field-error"></span>
                    </div>
                </div>
            </div>

            <div class="wcflow-form-section">
                <h3 class="wcflow-form-section-title">Delivery Address</h3>
                
                <div class="wcflow-form-row two-columns">
                    <div class="wcflow-form-group floating-label">
                        <input type="text" id="wcflow-shipping-first-name" placeholder=" " value="<?php echo esc_attr(WC()->customer->get_shipping_first_name()); ?>" required autocomplete="given-name">
                        <label for="wcflow-shipping-first-name">Gavėjo vardas *</label>
                        <span class="wcflow-field-error"></span>
                    </div>
                    <div class="wcflow-form-group floating-label">
                        <input type="text" id="wcflow-shipping-last-name" placeholder=" " value="<?php echo esc_attr(WC()->customer->get_shipping_last_name()); ?>" required autocomplete="family-name">
                        <label for="wcflow-shipping-last-name">Gavėjo pavardė *</label>
                        <span class="wcflow-field-error"></span>
                    </div>
                </div>

                <div class="wcflow-form-row">
                    <div class="wcflow-form-group floating-label country-select">
                        <select id="wcflow-shipping-country" required autocomplete="country">
                            <option value=""></option>
                            <?php
                            $countries = WC()->countries->get_shipping_countries();
                            $default_country = WC()->customer->get_shipping_country() ?: 'LT';
                            foreach ($countries as $code => $name) {
                                $selected = ($code === $default_country) ? 'selected' : '';
                                echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($name) . '</option>';
                            }
                            ?>
                        </select>
                        <label for="wcflow-shipping-country">Valstybė (šalis) *</label>
                        <span class="wcflow-field-error"></span>
                    </div>
                </div>

                <div class="wcflow-form-row">
                    <div class="wcflow-form-group floating-label">
                        <input type="text" id="wcflow-shipping-address-1" placeholder=" " value="<?php echo esc_attr(WC()->customer->get_shipping_address_1()); ?>" required autocomplete="address-line1">
                        <label for="wcflow-shipping-address-1">Pristatymo adresas *</label>
                        <span class="wcflow-field-error"></span>
                    </div>
                </div>
                
                <div class="wcflow-form-row two-columns">
                    <div class="wcflow-form-group floating-label">
                        <input type="text" id="wcflow-shipping-city" placeholder=" " value="" required autocomplete="address-level2">
                        <label for="wcflow-shipping-city">Miestas (vietovė) *</label>
                        <span class="wcflow-field-error"></span>
                    </div>
                    <div class="wcflow-form-group floating-label">
                        <input type="text" id="wcflow-shipping-postcode" placeholder=" " value="" required autocomplete="postal-code">
                        <label for="wcflow-shipping-postcode">Pašto kodas *</label>
                        <span class="wcflow-field-error"></span>
                    </div>
                </div>

                <div class="wcflow-form-row">
                    <div class="wcflow-form-group floating-label">
                        <input type="tel" id="wcflow-shipping-phone" placeholder=" " value="<?php echo esc_attr(WC()->customer->get_shipping_phone() ?: WC()->customer->get_billing_phone()); ?>" required autocomplete="tel">
                        <label for="wcflow-shipping-phone">Gavėjo telefono numeris *</label>
                        <span class="wcflow-field-error"></span>
                    </div>
                </div>
            </div>

            <div class="wcflow-form-section">
                <h3 class="wcflow-form-section-title">Delivery Options</h3>
                
                <div class="wcflow-form-row two-columns">
                    <div class="wcflow-form-group selectable-box required-field" id="wcflow-delivery-date-selector" data-required="true">
                        <span class="selectable-box-label">Pristatymo diena *</span>
                        <span class="selectable-box-value">Pasirinkite datą</span>
                        <span class="selectable-box-arrow">→</span>
                        <span class="wcflow-field-error"></span>
                    </div>
                    <div class="wcflow-form-group selectable-box required-field" id="wcflow-shipping-method-selector" data-required="true">
                        <span class="selectable-box-label">Pristatymo būdas *</span>
                        <span class="selectable-box-value">Loading...</span>
                        <span class="selectable-box-arrow">→</span>
                        <span class="wcflow-field-error"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="wcflow-bottom-bar">
        <div class="wcflow-content-wrapper">
            <div class="wcflow-bottom-bar-inner">
                <div class="wcflow-order-summary">
                    <div class="wcflow-order-total-line">
                        <span class="wcflow-order-label">Užsakymo suma</span>
                        <span class="wcflow-order-amount" id="wcflow-dynamic-total">0.00 €</span>
                    </div>
                    <div class="wcflow-order-details">
                        <span class="wcflow-order-breakdown" id="wcflow-shipping-details">Įskaičiuotas 0.00 € pristatymo mokestis</span>
                    </div>
                </div>
                <div class="wcflow-bottom-bar-action">
                    <button type="button" class="wcflow-btn-next wcflow-bottom-bar-btn">Tęsti</button>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Delivery Date Picker Popup -->
    <div id="wcflow-datepicker-popup" class="wcflow-popup-overlay" style="display:none;">
        <div class="wcflow-popup-content wcflow-calendar-popup">
            <!-- Calendar will be populated by JavaScript -->
        </div>
    </div>
    
    <!-- Shipping Method Popup -->
    <div id="wcflow-shipping-popup" class="wcflow-popup-overlay" style="display:none;">
        <div class="wcflow-popup-content wcflow-shipping-popup">
            <!-- Shipping methods will be populated by JavaScript -->
        </div>
    </div>
    
    <!-- Login Popup -->
    <div id="wcflow-login-popup" class="wcflow-popup-overlay" style="display:none;">
        <div class="wcflow-popup-content wcflow-login-popup-content">
            <button class="wcflow-popup-close" aria-label="Close">&times;</button>
            <h3>Prisijungti prie paskyros</h3>
            <p>Sign in to auto-fill your information and speed up checkout.</p>
            <form id="wcflow-login-form">
                <div class="wcflow-form-group floating-label">
                    <input type="text" id="wcflow-login-username" placeholder=" " required autocomplete="username">
                    <label for="wcflow-login-username">Naudotojo vardas arba el. paštas</label>
                </div>
                <div class="wcflow-form-group floating-label">
                    <input type="password" id="wcflow-login-password" placeholder=" " required autocomplete="current-password">
                    <label for="wcflow-login-password">Slaptažodis</label>
                </div>
                <div class="wcflow-login-actions">
                    <button type="submit" class="wcflow-login-btn">Prisijungti</button>
                </div>
                <div id="wcflow-login-error" class="wcflow-login-error" style="display:none;"></div>
            </form>
        </div>
    </div>
</div>

<!-- Step 2 loaded at 2025-06-18 09:46:30 UTC by justasskh -->
