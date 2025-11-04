<?php
// FILE: jorgeortega-ux/projectgenesis/ProjectGenesis-18ab9061f5940223dc8e2888c4e57a51b712dc78/ProjectGenesis/includes/sections/auth/login.php
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'login') ? 'active' : 'disabled'; ?>" data-section="login">
    <div class="auth-container">
        <h1 class="auth-title" data-i18n="page.login.title"></h1>
        
        <form class="auth-form" id="login-form" onsubmit="event.preventDefault();" novalidate>
            
            <?php outputCsrfInput(); ?>

            <fieldset class="auth-step active" data-step="1">
                <p class="auth-verification-text mb-16" data-i18n="page.login.step1Desc"></p>
                <div class="auth-input-group">
                    <input type="email" id="login-email" name="email" required placeholder=" ">
                    <label for="login-email" data-i18n="page.login.emailLabel"></label>
                </div>

                <div class="auth-input-group">
                    <input type="password" id="login-password" name="password" required placeholder=" ">
                    <label for="login-password" data-i18n="page.login.passwordLabel"></label>
                    <button type="button" class="auth-toggle-password" data-toggle="login-password">
                        <span class="material-symbols-rounded">visibility</span>
                    </button>
                </div>

                <p class="auth-link text-right">
                    <a href="/ProjectGenesis/reset-password" data-i18n="page.login.forgotPassword"></a>
                </p>
                <div class="auth-step-buttons">
                    <button type="button" class="auth-button" data-auth-action="next-step" data-i18n="page.login.continueButton"></button>
                </div>
                <div class="auth-error-message"></div>
            </fieldset>
            
            <fieldset class="auth-step" data-step="2">
                <p class="auth-verification-text mb-16" data-i18n="page.login.2faDescription"></p>

                <div class="auth-input-group">
                    <input type="text" id="login-code" name="verification_code" required placeholder=" " maxlength="14">
                    <label for="login-code" data-i18n="page.login.2faCodeLabel"></label>
                </div>
                
                <div class="auth-step-buttons">
                    <button type="submit" class="auth-button" data-i18n="page.login.verifyButton"></button>
                </div>
                
                <div class="auth-error-message"></div>
                <p class="auth-link text-center">
                    <a href="#" 
                       id="login-resend-code-link" 
                       data-auth-action="resend-code"
                       data-cooldown="0" 
                       data-i18n="page.login.resendCode"
                    >
                    </a>
                </p>
            </fieldset>
            
            </form>

        <p class="auth-link">
            <span data-i18n="page.login.noAccount"></span> <a href="/ProjectGenesis/register" data-i18n="page.login.createAccount"></a>
        </p>
    </div>
</div>