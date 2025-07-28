<?php
// Set the namespace defined in your config file
namespace MCRI\BMIPercentile;
// The next 2 lines should always be included and be the same in every module
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
// Declare your module class, which must extend AbstractExternalModule 
class BMIPercentile extends AbstractExternalModule {



    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) 
    {   
        //Setting current location and directory
        $currentLocation = __FILE__;
        $directory = dirname($currentLocation);
        
        //Reading files 
        $files = [
            'bmi_to_age' => 'bmi_age.csv',
            'zscore' => 'zscore.csv',
            'wtage' => 'wtage.csv',
            'statage' => 'statage.csv',
            'wfl' => 'weight_for_length.csv',
            'bmi_to_age_month' => 'bmi_age_month.csv',
            'wtage_month' => 'wtage_month.csv',
            'statage_month' => 'statage_month.csv',
            'wfl_month' => 'weight_for_length_month.csv',
            'bmi_to_age_month_cdc' => 'bmi_age_month_cdc.csv',
            'wtage_month_cdc' => 'wtage_month_cdc.csv',
            'statage_month_cdc' => 'statage_month_cdc.csv'
        ];

        foreach ($files as $varName => $file) {
            $filePath = $directory . '/' . $file;

            if (($open = fopen($filePath, "r")) !== FALSE) {
                while (($csvData = fgetcsv($open, 1000, ",")) !== FALSE) {
                    ${$varName}[] = $csvData; // Assigning to variable variable
                }
                fclose($open);
            }
        }

        // Getting field names from project settings
        $age_days =$this->getProjectSetting('age_days');
        $gender =$this->getProjectSetting('gender');
        $height =$this->getProjectSetting('height');
        $weight =$this->getProjectSetting('weight');
        $bmi =$this->getProjectSetting('bmi');
        $ht_zscore = $this->getProjectSetting('ht_zscore');
        $ht_pct = $this->getProjectSetting('ht_pct');
        $wt_zscore = $this->getProjectSetting('wt_zscore');
        $wt_pct = $this->getProjectSetting('wt_pct');
        $bmi_zscore = $this->getProjectSetting('bmi_zscore');
        $bmi_pct = $this->getProjectSetting('bmi_pct');
        $wfl_zscore = $this->getProjectSetting('wfl_zscore');
        $wfl_pct = $this->getProjectSetting('wfl_pct');
        $cdc_sel = $this->getProjectSetting('cdc_sel');



        ?>
        <script type="text/javascript">

            var z_score_val= 0;
            var above_95_flag = 0;

            // Getting file values from php to js
            var zscore = <?php echo json_encode($zscore); ?>;
            var bmi_to_age = <?php echo json_encode($bmi_to_age); ?>;
            var wtage = <?php echo json_encode($wtage); ?>;
            var statage = <?php echo json_encode($statage); ?>;
            var wfl_d = <?php echo json_encode($wfl); ?>;
            var bmi_to_age_month = <?php echo json_encode($bmi_to_age_month); ?>;
            var wtage_month = <?php echo json_encode($wtage_month); ?>;
            var statage_month = <?php echo json_encode($statage_month); ?>;
            var wfl_d_month = <?php echo json_encode($wfl_month); ?>;
            var bmi_to_age_month_cdc = <?php echo json_encode($bmi_to_age_month_cdc); ?>;
            var wtage_month_cdc = <?php echo json_encode($wtage_month_cdc); ?>;
            var statage_month_cdc = <?php echo json_encode($statage_month_cdc); ?>;

            // Getting field names from php to js
            var age_field = "<?php echo $age_days; ?>";
            var gender_field = "<?php echo $gender; ?>";
            var ht_field = "<?php echo $height; ?>";
            var wt_field = "<?php echo $weight; ?>";
            var bmi_field = "<?php echo $bmi; ?>";
            var ht_zscore_field = "<?php echo $ht_zscore; ?>";
            var ht_pct_field = "<?php echo $ht_pct; ?>";
            var wt_zscore_field = "<?php echo $wt_zscore; ?>";
            var wt_pct_field = "<?php echo $wt_pct; ?>";
            var bmi_zscore_field = "<?php echo $bmi_zscore; ?>";
            var bmi_pct_field = "<?php echo $bmi_pct; ?>";
            var wfl_zscore_field = "<?php echo $wfl_zscore; ?>";
            var wfl_pct_field = "<?php echo $wfl_pct; ?>";
            var cdc_sel = "<?php echo $cdc_sel; ?>";

            // Below function converts zscore to %ile
            function normalCDF(z) {
                return 0.5 * (1 + erf(z / Math.sqrt(2)));
            }

            // Approximation of error function (erf)
            function erf(x) {
                // constants
                var a1 =  0.254829592,
                    a2 = -0.284496736,
                    a3 =  1.421413741,
                    a4 = -1.453152027,
                    a5 =  1.061405429,
                    p  =  0.3275911;

                var sign = (x >= 0) ? 1 : -1;
                x = Math.abs(x);

                var t = 1 / (1 + p * x);
                var y = 1 - (((((a5 * t + a4) * t) + a3) * t + a2) * t + a1) * t * Math.exp(-x * x);

                return sign * y;
            }


            // Function which calculates and stores zscores and BMI
            function ret_percentile(choice){
            	
                gender = $('input[name="'+gender_field+'___radio"]:checked').val();
                age = $('input[type=text][name="'+age_field+'"]').val();
                var age_in_months = Number(((age / 30.4375)).toFixed(2));
                var age_month_floor = Math.floor(age_in_months);
                var age_month_ceil = Math.ceil(age_in_months);
            	
                var z_field = "";
                var pct_field = "";

                var l = 1;
                var m = 1;
                var s = 1;


                // Below code selects csv according to the age.
                // If age is below 60 months, it will take file with age as days, and wfl
                // If the age is above 60 months, it will select file with age as months of who or cdc according to cdc_sel value
                const isOver60 = age_month_floor > 60;
                age = isOver60 ? age_in_months : age;

                const dataMap = {
                    //bmi file
                    bmi: {
                        under60: bmi_to_age,
                        over60: cdc_sel == 1 ? bmi_to_age_month_cdc : bmi_to_age_month
                    },

                    //weight file
                    pat_weight: {
                        under60: wtage,
                        over60: cdc_sel == 1 ? wtage_month_cdc : wtage_month
                    },

                    //height file
                    pat_height: {
                        under60: statage,
                        over60: cdc_sel == 1 ? statage_month_cdc : statage_month
                    },

                    //WeightforLength file
                    pat_wfl: {
                        under60: wfl_d
                    }
                };

                //Final file is selected according to the conditions such as age, who/cdc
                who_data = isOver60 ? dataMap[choice]?.over60 : dataMap[choice]?.under60;

                // Iterating through the file
                for (var i = 1; i <who_data.length; i++) {
                        
                        if(choice == "bmi"){                        
                            //Calculating precise bmi
                            x = $('input[type=text][name="'+wt_field+'"]').val()/(($('input[type=text][name="'+ht_field+'"]').val()/100)**2);
                            z_field = bmi_zscore_field;
                            pct_field = bmi_pct_field;
                        }
                        else if(choice == "pat_weight"){
                            x = $('input[type=text][name="'+wt_field+'"]').val();
                            z_field = wt_zscore_field;
                            pct_field = wt_pct_field;
                        }
                        else if(choice == "pat_wfl"){
                            //wfl uses x as weight
                            x = $('input[type=text][name="'+wt_field+'"]').val();
                            z_field = wfl_zscore_field;
                            pct_field = wfl_pct_field;
                        }
                        else if(choice == "pat_height"){
                            x = $('input[type=text][name="'+ht_field+'"]').val();
                            z_field = ht_zscore_field;
                            pct_field = ht_pct_field;
                        }
                        
                        
                        // If age (days), gender matches with a row, lms will be set - Below is for age<=60 months
                        if(age==Math.ceil(parseFloat(who_data[i][1])) && gender==Math.floor(parseFloat(who_data[i][0])) &&  choice != "pat_wfl" && age_month_floor<=60){
                            val_found = 1;
                            l = parseFloat(who_data[i][2]);
                            m = parseFloat(who_data[i][3]);
                            s = parseFloat(who_data[i][4]);
                            //Z score equation
                            z_score_val= (((x/m)**l)-1)/(l*s);
                        }

                        // If age (months) is between (i)th age and (i+1)th age and gender matches, lms interpolate is calculated
                        // Example: if 70.4
                        // CDC: ith value should be 69.5, i+1 will be 70.5
                        // WHO: ith value should be 70, i+1 will be 71
                        if(age_month_floor>60 && i < (who_data.length -1) && gender==Math.floor(parseFloat(who_data[i][0])) && age>=parseFloat(who_data[i][1]) && age<=parseFloat(who_data[i+1][1]) ){

                            // Age diff = (Age-Age0)
                            // Example:
                            // CDC : 70.4-69.5 = 0.9
                            // WHO : 70.4 - 70 = 0.4
                            var age_diff = parseFloat((age_in_months - parseFloat(who_data[i][1])).toFixed(5));
                            // If difference too small, ignore it
                            if(age_diff<=0.02){
                                age_diff = 0;
                            }
                            // l = l1 + ((l2-l1)*age_diff), where l1 = (i)th value, and l2 = (i+1)th value
                            l = parseFloat(who_data[i][2]) + ((parseFloat(who_data[i+1][2]) - parseFloat(who_data[i][2]))*age_diff);
                            m = parseFloat(who_data[i][3]) + ((parseFloat(who_data[i+1][3]) - parseFloat(who_data[i][3]))*age_diff);
                            s = parseFloat(who_data[i][4]) + ((parseFloat(who_data[i+1][4]) - parseFloat(who_data[i][4]))*age_diff);

                            //Z score equation
                            z_score_val= (((x/m)**l)-1)/(l*s);
                                
                        }


                        // Below code is for wfl. First it will find row with same gender and height
                        if (choice == "pat_wfl" && gender == Math.floor(parseFloat(who_data[i][0])) && parseFloat($('input[type=text][name="'+ht_field+'"]').val()) == parseFloat(who_data[i][2]))
                        {
                            // WFL is divided in 2 parts, 60 (this if for children aging between 731 to 1856 days) and 24 (age < 731 days)
                            if((Math.floor(parseFloat(who_data[i][1]))==60 && age>=731 && age<=1856) || (Math.floor(parseFloat(who_data[i][1]))==24 && age<731)){
                                val_found = 1;

                                var l = parseFloat(who_data[i][3]);
                                var m = parseFloat(who_data[i][4]);
                                var s = parseFloat(who_data[i][5]);
                                //Z score equation
                                z_score_val= (((x/m)**l)-1)/(l*s);
                            }
                            
                        }


                    }
                    
                    //Rounding zscore
                    z_score_val = parseFloat(z_score_val.toFixed(2));
                    $('input[type=text][name="'+z_field+'"]').val(z_score_val);
                    //Z score to %ile
                    var perc = (normalCDF(z_score_val) * 100).toFixed(2);
                    $('input[type=text][name="'+pct_field+'"]').val(perc);

                    z_score_flag = 1;

                    
                    
                    if (z_score_val>3.0){
                        $('input[type=text][name="'+pct_field+'"]').val("99.99");
                        $('input[type=text][name="'+z_field+'"]').val(z_score_val);
                    }
                    else if (z_score_val<-3.0){
                        $('input[type=text][name="'+pct_field+'"]').val("0.01");
                        $('input[type=text][name="'+z_field+'"]').val(z_score_val);
                    }

                    
            }

            function calc_percentile(){

                //Initial conditions check age, and gender
                if($('input[type=text][name="'+age_field+'"]').val() >=0 && $('input[type=text][name="'+age_field+'"]').val() <=7310 && $('input[type=radio][name="'+gender_field+'___radio"]:checked').val()>=0){
                    //Conditions to if BMI percentile can be calculated
                    if($('input[type=text][name="'+bmi_field+'"]').val()!='' && $('input[type=text][name="'+bmi_field+'"]').val()>=0){
                        ret_percentile("bmi");
                    }else{
                        $('input[type=text][name="'+bmi_pct_field+'"]').val("");
                        $('input[type=text][name="'+bmi_zscore_field+'"]').val("");
                    }

                    //Conditions to if Height percentile can be calculated
                    if($('input[type=text][name="'+ht_field+'"]').val()!='' && $('input[type=text][name="'+ht_field+'"]').val()>=0){
                        ret_percentile("pat_height");

                        if($('input[type=text][name="'+ht_field+'"]').val()>=45 && $('input[type=text][name="'+ht_field+'"]').val()<=120 && $('input[type=text][name="'+age_field+'"]').val() <=1826){
                            ret_percentile("pat_wfl");
                        }
                        else{
                            $('input[type=text][name="'+wfl_zscore_field+'"]').val("");
                            $('input[type=text][name="'+wfl_pct_field+'"]').val("");
                        }
                    }
                    else{
                        $('input[type=text][name="'+ht_pct_field+'"]').val("");
                        $('input[type=text][name="'+ht_zscore_field+'"]').val("");
                    }

                    //Conditions to if Weight percentile can be calculated
                    if($('input[type=text][name="'+wt_field+'"]').val()!=='' && $('input[type=text][name="'+ht_field+'"]').val()>=0){
                        ret_percentile("pat_weight");
                    }
                    else{
                        $('input[type=text][name="'+wt_pct_field+'"]').val("");
                        $('input[type=text][name="'+wt_zscore_field+'"]').val("");
                    }

                }
               
            }
            
            //Calculating percentile when height, weight, or gender is changed.

            
            $('input[type=text][name="'+ht_field+'"]').change(function() {
                calc_percentile();
            });
            $('input[type=text][name="'+wt_field+'"]').change(function() {
                calc_percentile();
            });
            $('input[type=text][name="'+bmi_field+'"]').change(function() {
                calc_percentile();
            });
            $('input[type=radio][name="'+gender_field+'___radio"]').change(function() {
                calc_percentile();
            });
            
        </script>
        <?php   
    }

}