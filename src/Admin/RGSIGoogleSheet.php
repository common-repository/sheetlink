<?php
namespace RAOGSI_COMPOSER\Admin;
/**
 * Handles all code associated with the Google Sheet
 */
use \Firebase\JWT\JWT;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
class RGSIGoogleSheet {
    #private key
    protected $private_key;

    #private key id
    protected $private_key_id;

    //Client email
    protected $client_email;

    //Client Id
    protected $client_id;

    protected $google_credentials;

    public function __construct() {
        $google_credentials = raogsi_get_google_service_account_credentials();
        $this->google_credentials = $google_credentials;
        if($google_credentials AND isset($google_credentials['private_key_id'], $google_credentials['private_key'], $google_credentials['client_email'], $google_credentials['client_id'])){
            $this->private_key = $google_credentials['private_key'];
            $this->private_key_id = $google_credentials['private_key_id'];
            $this->client_email = $google_credentials['client_email'];
            $this->client_id = $google_credentials['client_id'];
        }
    }

    public function raogsi_token(){
		# getting google token 
        $access_token = false;
		$google_token = get_option('raogsi_google_token', FALSE);
		# Checking Token Validation
		if($google_token  &&  time() > $google_token['expires_in'] || !$google_token){
			# if Credentials & Not empty
			$new_token = $this->raogsi_generatingTokenByCredentials();
			# Check & Balance
			if($new_token[0]){
				# Change The Token Info
				$new_token[1]['expires_in'] = time() + $new_token[1]['expires_in'];
				# coping The Token
				$access_token = $new_token[1]['access_token'];
				# Save in Options
				update_option('raogsi_google_token', $new_token[1]);
			}else{
		
				# return the valid token;
				return false;
			}
		} else {
            if(isset($google_token['access_token']))
            $access_token = $google_token['access_token'];
        }
		
		# return the valid token;
		return $access_token;
	}

    /**
     * Generate Access Token by Credentials
     * 
     */
    public function raogsi_generatingTokenByCredentials() {
        if( empty( $this->private_key ) ) {
            return array( false, array( 'code' => 420, 'message' => "Private Key is empty" ) );
        }

        if( empty( $this->private_key_id ) ) {
            return array( false, array( 'code' => 420, 'message' => "Private Key ID is empty" ) );
        }

        if( empty( $this->client_id ) ) {
            return array( false, array( 'code' => 420, 'message' => "Client ID is empty" ) );
        }

        if( empty( $this->client_email ) ) {
            return array( false, array( 'code' => 420, 'message' => "Client Email is empty" ) );
        }

        # Creating payload
		$payload = array(
		    "iss" 	=>  $this->google_credentials['client_email'],
		    "scope"	=> 'https://www.googleapis.com/auth/drive',
		    "aud" 	=> 'https://oauth2.googleapis.com/token',
		    "exp"	=>	time()+3600,
		    "iat" 	=> 	time(),
		);

		$jwt  = JWT::encode($payload, $this->google_credentials['private_key'], 'RS256');

		$args = array(
		    'headers' => array(),
		    'body'    => array(
	            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
	            'assertion'  => $jwt,
	        )
		);
		# Token url Remote request 
		$returns  =  wp_remote_post('https://oauth2.googleapis.com/token', $args);
        
		# Check & Balance 
		if(is_wp_error($returns) OR !is_array($returns) OR !isset($returns['body'])){
			return array(FALSE, "ERROR :  on token Creation." . wp_json_encode($returns, TRUE));
		} else {
			# inserting SUCCESS log
			return array(TRUE, json_decode($returns['body'], TRUE));
		}
    }

    /**
     * Validate Google Credentials
     */
    public function raogsi_validate_credentials() {
        $access_token = $this->raogsi_token();
        if( !$access_token ) {
        
            return new \WP_Error( 'raogsi_invalid_token', 'Invalid Token' );
        }
        $request = wp_remote_get( "https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=" . $access_token);
        if( is_wp_error(( $request) ) OR ! isset($request['response']['code'])  OR $request['response']['code'] != 200){
            return new \WP_Error( 'raogsi_invalid_credentials', 'Invalid Credentials' );
        } else {
            return array(true, $request['body']);
        }
    }

    /**
     * Retrieve Google Spreadsheets & Worksheets
     * 
     * @since v1.0.0
     */
    public function raogsi_spreadsheetsAndWorksheets() {
        $access_token = $this->raogsi_token();
        
        if( !$access_token ) {
        
            return new \WP_Error( 'raogsi_invalid_token', 'Invalid Token' );
        }
        
        $spreadsheet_data = \wp_remote_get("https://www.googleapis.com/drive/v3/files?access_token=" . $access_token);
        
        if(is_wp_error($spreadsheet_data))
        {
            
            return new \WP_Error ('raogsi_spreadsheet_error', wp_json_encode($spreadsheet_data));
        }
        $body 					= json_decode(wp_remote_retrieve_body($spreadsheet_data), true);
		$files 					= $body['files'];
        
		$spreadsheets 			= array();
		$spreadsheetsWorksheet  = array();
        
        foreach($files as $file){
			if($file['mimeType'] == "application/vnd.google-apps.spreadsheet"){
				$spreadsheets[ $file['id'] ] = $file['name'];
			}
		}

        # Getting worksheets of those spreadsheets
		foreach($spreadsheets as $spreadsheetsKey => $spreadsheetsName){
			# Creating URL 
            $access_token = $this->raogsi_token();
			$worksheetsReturns = wp_remote_get("https://sheets.googleapis.com/v4/spreadsheets/" . $spreadsheetsKey . "/?access_token=" . $access_token);
			# There Maybe an ERROR || Object as array ;
			if(! is_wp_error($worksheetsReturns) && isset($worksheetsReturns['response']['code']) && $worksheetsReturns['response']['code'] == 200){
				# JSON to PHP Array;
				$worksheetsResponseBody = json_decode($worksheetsReturns['body'], TRUE);
				# Temporary worksheets Holder;
				$sheets = array();
				# Looping spreadsheets;
				foreach($worksheetsResponseBody['sheets'] as $value){
					$sheets[$value['properties']['sheetId']] = $value['properties']['title'];
				}
				# Populating $spreadsheetsWorksheet Array For Output ;
				$spreadsheetsWorksheet[$spreadsheetsKey] = array($spreadsheetsName, $sheets);
			}else{
				return array(FALSE, wp_json_encode($worksheetsReturns));
			}
		}
		# Returns || Remember It's an array so Git tha value on that Way ;
		return array(TRUE, $spreadsheetsWorksheet);
    }

    public function raogsi_columnTitle($worksheet_name = '',  $spreadsheets_id = ''){
		
		# check worksheet_name is empty or not  
		if(empty($worksheet_name)){
			return array( FALSE, "ERROR: worksheet_name is Empty. from  wpgsi_columnTitle func");
		}
		# Check spreadsheets_id is empty or not
		if(empty($spreadsheets_id)){
			return array( FALSE, "ERROR: spreadsheets_id is Empty. from  wpgsi_columnTitle func");
		}
        $access_token = $this->raogsi_token();
		# If passed parameter is Array and Not String  || Creating Query URL
		$request = wp_remote_get( 'https://sheets.googleapis.com/v4/spreadsheets/' . $spreadsheets_id . '/values/' . $worksheet_name . '!A1:YZ1?access_token=' . $access_token);
		
		# If Not response code is not 200 then return ERROR with ERROR code 
		if(is_wp_error($request) OR ! isset($request['response']['code']) OR $request['response']['code'] != 200){
			return new \wp_error('rest_invalid_column_request','Invalid Column Request');
		}

		# Converting json body into PHP array 
		$responseBody = json_decode($request['body'], TRUE);
		
		# If There are no column title or First ROW is Empty Then Send a Arry with key without value 
		if(! isset($responseBody['values'][0])){
        	return array("A1"=>"","B1"=>"","C1"=>"","D1"=>"","E1"=>"","F1"=>"","G1"=>"","H1"=>"","I1"=>"","J1"=>"","K1"=>"","L1"=>"","M1"=>"","N1"=>"","O1"=>"","P1"=>"","Q1"=>"","R1"=>"","S1"=>"","T1"=>"","U1"=>"","V1"=>"","W1"=>"","X1"=>"","Y1"=>"","Z1"=>"");
		}
 
		$key_array = array();
		for($i = "A"; $i < 'ZZ' ; $i++ ){
           $temp = $i . "1"; 
			array_push($key_array, $temp);
		}
		
		# Combining arrays for return 
		$columnKeyTitle  = array_combine(array_slice($key_array, 0, count($responseBody['values'][0])), $responseBody['values'][0]);
		
		return $columnKeyTitle;
	}

	public function raogsi_append_row( $spreadsheet_id, $worksheet_name, $data = [] ) {
		if( empty( $spreadsheet_id ) ) {
			return new \WP_Error( 'raogsi-invalid-spreadsheet-id', 'Invalid Spreadsheet ID');
		}

		if( empty( $worksheet_name ) ) {
			return new \WP_Error( 'raogsi-invalid-worksheet-name', 'Invalid Worksheet Name');
		}

		if( !is_array( $data ) || empty( $data ) ) {
			return new \WP_Error( 'raogsi-invalid-data', 'Invalid Data');
		}
		$access_token = $this->raogsi_token();
		$url  = "https://sheets.googleapis.com/v4/spreadsheets/" . $spreadsheet_id . "/values/" . $worksheet_name . "!A:A:append?valueInputOption=USER_ENTERED";
		$values = wp_json_encode(array_values($data));
		$args = array(
			'headers'	=>	array(
				'Authorization'	=>	'Bearer '.$access_token	,
				'Content-Type'	=>	'application/json',
			),
			'body'		=> '{"range":"' . $worksheet_name . '!A:A", "majorDimension":"ROWS", "values":['.wp_json_encode(array_values($data)).']}'
		);

		$apiResponse  = wp_remote_post( $url, $args );
		$apiBody     = json_decode( wp_remote_retrieve_body( $apiResponse ) );
		return $apiBody;
	}

	public function raogsi_delete_row( $spreadsheet_id, $worksheet_name, $data, $found_key) {
		if( !$found_key )
		return;
		
		$first_key = preg_replace('/\d/', '', array_key_first( $data ) ).$found_key;
		$last_key = preg_replace('/\d/', '', array_key_last( $data ) ).$found_key;
		
		$access_token = $this->raogsi_token();
		$spreadsheets_and_worksheets = $this->raogsi_spreadsheetsAndWorksheets();
		
		if( is_wp_error( $spreadsheets_and_worksheets ) )
		return;
		$worksheets_data = $spreadsheets_and_worksheets[1];
		
		if(!isset( $worksheets_data[$spreadsheet_id]))
		return;
		$worksheet_id = array_search( $worksheet_name, $worksheets_data[$spreadsheet_id][1]);
		
		$body_params['requests'][] = array(
			'deleteDimension' => array(
				'range'	=>	array(
					'sheetId'	=>	$worksheet_id,
					'dimension' => 'ROWS',
					'startIndex'=> $found_key - 1,
					'endIndex'	=>	$found_key
				)
			)
		);
		$args = array(
			'headers'	=>	array(
				'Authorization'	=>	'Bearer '.$access_token	,
				'Content-Type'	=>	'application/json',
			),
			'body'		=> wp_json_encode($body_params),
			'method'	=>	'POST'
		);
		
		
		$url = 'https://sheets.googleapis.com/v4/spreadsheets/'.$spreadsheet_id.':batchUpdate';
		$apiResponse = wp_remote_request($url, $args);
		$apiBody     = json_decode( wp_remote_retrieve_body( $apiResponse ) );
		
		return $apiBody;
		

	}

	public function raogsi_fetch_data( $spreadsheet_id, $worksheet_name, $data, $index = "A" ) {
		$index = $index.":".$index;
		$access_token = $this->raogsi_token();
		$url = 'https://sheets.googleapis.com/v4/spreadsheets/'. $spreadsheet_id . '/values/' . $worksheet_name . '!'.$index.'?majorDimension=COLUMNS&valueRenderOption=UNFORMATTED_VALUE';

		$args = array(
			'headers'	=>	array(
				'Authorization'	=>	'Bearer '.$access_token	,
				'Content-Type'	=>	'application/json',
			),
		);
		$apiResponse = wp_remote_get($url,$args);
		$apiBody     = json_decode( wp_remote_retrieve_body( $apiResponse ) );
		return $apiBody;
	}

	public function raogsi_update_row( $spreadsheet_id, $worksheet_name, $data, $row_key ) {
		$access_token = $this->raogsi_token();
		$url = 'https://sheets.googleapis.com/v4/spreadsheets/'. $spreadsheet_id . '/values/' . $worksheet_name . "!".$row_key."?valueInputOption=USER_ENTERED";
		$args = array(
			'headers'	=>	array(
				'Authorization'	=>	'Bearer '.$access_token	,
				'Content-Type'	=>	'application/json',
			),
			'body'		=> '{"range":"' . $worksheet_name . '!'.$row_key.'", "majorDimension":"ROWS", "values":['.wp_json_encode(array_values($data)).']}',
			'method'	=>	'PUT'
		);

		$apiResponse = wp_remote_get($url, $args);
		$apiBody     = json_decode( wp_remote_retrieve_body( $apiResponse ) );
		return $apiBody;
	}
}

