<?php
namespace local_classroom\upload;
/**
 * Validation callback function - verified the column line of csv file.
 * Converts standard column names to lowercase.
 * @param csv_import_reader $cir
 * @param array $stdfields standard user fields
 * @param array $profilefields custom profile fields
 * @param moodle_url $returnurl return url in case of any error
 * @return array list of fields
 */
use csv_import_reader;
use moodle_url;
use core_text;
class progresslibfunctions{
    /**
     * [uu_validate_user_upload_columns description]
     * @param  csv_import_reader $cir           [description]
     * @param  array             $stdfields     [standarad fields in user table]
     * @param  array             $profilefields [profile fields in user table]
     * @param  moodle_url        $returnurl     [moodle return page url]
     * @return array                            [validated fields in csv uploaded]
     */
    public function uu_validate_user_upload_columns(csv_import_reader $cir, $stdfields, $profilefields, moodle_url $returnurl) {

        $columns = $cir->get_columns();
        // test columns
        $processed = array();

        foreach ($columns as $key => $unused) {

            $field = $columns[$key];
          //
            $lcfield = core_text::strtolower($field);
            if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
               // standard fields are only lowercase
                $newfield = $lcfield;
            } else{
              echo '<div class=local_classroom_sync_error>Invalid field data in uploaded excelsheet '.$key.'</div>';
               continue;
           }
         // }
            $processed[$key] = $newfield;

        }

        return $processed;
    }
}
