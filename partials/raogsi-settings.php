<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  ?>
<div class="wrap">
    <h1><?php esc_attr_e("Google Service Account Settings", 'gsheets-connector') ?></h1>
    <div id="">
        <form name="raogsa_settings" method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ) ?>">
        <input type="hidden" name="wp_raogsi_settings" id="wp_raogsi_settings" value="<?php echo esc_attr(wp_create_nonce('wp-raogsi-settings'));?>" />
            <input type="hidden" name="action" value="rao_google_settings" />
            <input type="hidden" name="nonce"  value="<?php  echo esc_attr(wp_create_nonce( 'raogsi-google-nonce' )) ; ?>" />
            <div class="raogsa_settings_wrap">
                <?php
                    if( is_array( $this->credentials ) && isset( $this->credentials['client_email'])) :
                        ?>
                        <!-- credential JSON Starts disabled -->
                            <p><b> Service Account email address : </b></p>
                            <textarea id="raogsa-credentials" name="raogsa-credential" cols="80" rows="8"  class="large-text" disabled > 
                                <?php 
                echo  "*** Share your Google spreadsheet with this service account email address :  " . esc_html( $this->credentials['client_email'] ) ;
                ?>
                            </textarea>
                            <br><br>
                            <!-- credential JSON Ends -->
                    <?php
                    echo  "<a href=" . esc_url(admin_url( 'admin-post.php?action=rao_google_settings&deleteCredential=1&nonce=' )) . esc_attr(wp_create_nonce( 'raogsa-google-nonce-delete' )) . " class='button-secondary' style=' text-decoration: none; color: #7f7f7f;'>  Remove Credential  </a>" ;
                    /*$ret = $this->googleSheet->wpgsi_token_validation_checker();
                    # Checking token is valid || Display it
                    
                    if ( $ret[0] ) {
                        echo  "<span style='vertical-align: middle;padding-top: 5px;' class='dashicons dashicons-yes'> </span>" ;
                        # if valid it will show tick
                    } else {
                        echo  "<span style='vertical-align: middle;padding-top: 5px;' class='dashicons dashicons-no'>  </span>" ;
                        # if false it will Show cross
                    }*/
                    else:
                    ?>
                    <p><b> Exactly copy the downloaded file Credentials, and Paste it here : </b></p>
                    <textarea id="raogsa-credential" name="raogsa-credential" cols="80" rows="8"  class="large-text">  </textarea>
                    <br><br>
                    <input type='submit' class='button-primary' name='save_btn'   value='Save' /> 
                    <?php
                    endif;
                ?>
            </div>
        </form>
    </div>
</div>
