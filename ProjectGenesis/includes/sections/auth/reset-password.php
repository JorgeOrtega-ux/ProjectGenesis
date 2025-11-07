<div class="section-content overflow-y <?php echo (strpos($CURRENT_SECTION, 'reset-') === 0) ? 'active' : 'disabled'; ?>" data-section="<?php echo htmlspecialchars($CURRENT_SECTION); ?>">
    <div class="auth-container">
        <h1 class="auth-title" data-i18n="page.reset.title"></h1>
        
        <form class="auth-form" id="reset-form" onsubmit="event.preventDefault();" novalidate>
            
            <?php outputCsrfInput(); ?>

            <fieldset class="auth-step <?php echo ($CURRENT_RESET_STEP == 1) ? 'active' : ''; ?>" data-step="1">
                 <p class="auth-verification-text mb-16" data-i18n="page.reset.step1Desc"></p>
                <div class="auth-input-group">
                    <input type="email" id="reset-email" name="email" required placeholder=" " maxlength="255">
                    <label for="reset-email" data-i18n="page.reset.emailLabel"></label>
                </div>
                
                <div class="auth-step-buttons">
                    <button type="button" class="auth-button" data-auth-action="next-step" data-i18n="page.reset.sendCodeButton"></button>
                </div>
                
                <div class="auth-error-message"></div>
                
                <p class="auth-link text-center">
                    <span data-i18n="page.reset.rememberedPassword"></span> <a href="/ProjectGenesis/login" data-i18n="page.reset.login"></a>
                </p>
            </fieldset>

            <fieldset class="auth-step <?php echo ($CURRENT_RESET_STEP == 2) ? 'active' : ''; ?>" data-step="2">
                
                <p class="auth-verification-text mb-16">
                    <span data-i18n="page.reset.step2Desc"></span> 
                    <strong><?php echo htmlspecialchars($_SESSION['reset_email'] ?? 'tu correo'); ?></strong>.
                </p>

                <div class="auth-input-group">
                    <input type="text" id="reset-code" name="verification_code" required placeholder=" " maxlength="14">
                    <label for="reset-code" data-i18n="page.reset.verificationCodeLabel"></label>
                </div>

                <div class="auth-step-buttons">
                    <button type="button" class="auth-button" data-auth-action="next-step" data-i18n="page.reset.verifyButton"></button>
                </div>
                
                <div class="auth-error-message"></div>
                
                <p class="auth-link text-center">
                    <a href="#" 
                       id="reset-resend-code-link" 
                       data-auth-action="resend-code"
                       data-cooldown="<?php echo isset($initialCooldown) ? $initialCooldown : 0; ?>"
                       class="<?php echo (isset($initialCooldown) && $initialCooldown > 0) ? 'disabled-interactive' : ''; ?>"
                       data-i18n="page.reset.resendCode"
                    >
                       <?php 
                       if (isset($initialCooldown) && $initialCooldown > 0) {
                           echo " (" . $initialCooldown . "s)";
                       }
                       ?>
                    </a>
                </p>
            </fieldset>
            <fieldset class="auth-step <?php echo ($CURRENT_RESET_STEP == 3) ? 'active' : ''; ?>" data-step="3">
                <p class="auth-verification-text mb-16" data-i18n="page.reset.step3Desc"></p>

                <div class="auth-input-group">
                    <input type="password" id="reset-password" name="password" required placeholder=" " minlength="8" maxlength="72">
                    <label for="reset-password" data-i18n="page.reset.newPasswordLabel"></label>
                    <button type="button" class="auth-toggle-password" data-toggle="reset-password">
                        <span class="material-symbols-rounded">visibility</span>
                    </button>
                </div>

                 <div class="auth-input-group">
                    <input type="password" id="reset-password-confirm" name="password_confirm" required placeholder=" " minlength="8" maxlength="72">
                    <label for="reset-password-confirm" data-i18n="page.reset.confirmPasswordLabel"></label>
                    <button type="button" class="auth-toggle-password" data-toggle="reset-password-confirm">
                        <span class="material-symbols-rounded">visibility</span>
                    </button>
                </div>
                
                <div class="auth-step-buttons">
                     <a href="/ProjectGenesis/reset-password/verify-code" class="auth-button-back" data-i18n="page.reset.backButton"></a>
                    <button type="submit" class="auth-button" data-i18n="page.reset.saveButton"></button>
                </div>
                
                <div class="auth-error-message"></div>
                
            </fieldset>
            
            </form>
    </div>
</div>