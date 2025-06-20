<?php
/**
 * WooCommerce Gifting Flow Step 2 - ENHANCED SHIPPING SECTION LAYOUT
 * Updated: 2025-01-27 - Improved shipping section with labels above fields and enhanced date picker
 */
?>
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
                        <input type="text" id="wcflow-shipping-city" placeholder=" " value="<?php echo esc_attr(WC()->customer->get_shipping_city()); ?>" required autocomplete="address-level2">
                        <label for="wcflow-shipping-city">Miestas (vietovė) *</label>
                        <span class="wcflow-field-error"></span>
                    </div>
                    <div class="wcflow-form-group floating-label">
                        <input type="text" id="wcflow-shipping-postcode" placeholder=" " value="<?php echo esc_attr(WC()->customer->get_shipping_postcode()); ?>" required autocomplete="postal-code">
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

            <!-- ENHANCED: Delivery Options Section with Labels Above Fields -->
            <div class="wcflow-form-section">
                <h3 class="wcflow-form-section-title">Delivery Options</h3>
                
                <div class="wcflow-form-row two-columns">
                    <!-- ENHANCED: Delivery Date with Label Above -->
                    <div class="wcflow-form-group">
                        <label class="wcflow-field-label">Pristatymo diena *</label>
                        <div class="selectable-box" id="wcflow-delivery-date-selector">
                            <div class="selectable-box-content">
                                <span class="selectable-box-value">Pasirinkite datą</span>
                                <span class="selectable-box-arrow">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 9l6 6 6-6"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <span class="wcflow-field-error"></span>
                    </div>
                    
                    <!-- ENHANCED: Shipping Methods with Label Above -->
                    <div class="wcflow-form-group">
                        <label class="wcflow-field-label">Pristatymo būdas</label>
                        <div class="selectable-box" id="wcflow-shipping-method-selector">
                            <div class="selectable-box-content">
                                <span class="selectable-box-value">Kraunami pristatymo būdai...</span>
                                <span class="selectable-box-arrow">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 9l6 6 6-6"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ENHANCED: Consistent Bottom Bar with Persistent Pricing -->
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
</div>