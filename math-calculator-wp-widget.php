<?php
/*
Plugin Name: Math Calculator
Plugin URI: http://www.calculator.net/projects/math-calculator-widget.php
Description: This calculator can be used for quick on site calculations. Install "Math Calculator" through the WordPress admin menu of Appearance or Design and then widgets to add to the sidebar.
Author: calculator.net
Version: 1.0
Author URI: http://www.calculator.net
License: GNU GPL see http://www.gnu.org/licenses/licenses.html#GPL
*/

class calculatornet_math_calculator {

    function calc_init() {
    	$class_name = 'calculatornet_math_calculator';
    	$calc_title = 'Math Calculator';
    	$calc_desc = 'This calculator can be used for quick on site calculations.';

    	if (!function_exists('wp_register_sidebar_widget')) return;

    	wp_register_sidebar_widget(
    		$class_name,
    		$calc_title,
    		array($class_name, 'calc_widget'),
            array(
            	'classname' => $class_name,
            	'description' => $calc_desc
            )
        );

    	wp_register_widget_control(
    		$class_name,
    		$calc_title,
    		array($class_name, 'calc_control'),
    	    array('width' => '100%')
        );

        add_shortcode(
        	$class_name,
        	array($class_name, 'calc_shortcode')
        );
    }

    function calc_display($is_widget, $args=array()) {
    	if($is_widget){
    		extract($args);
			$options = get_option('calculatornet_math_calculator');
			$title = $options['title'];
			$output[] = $before_widget . $before_title . $title . $after_title;
		}


		$output[] = '<div style="margin-top:5px;">
			<script type="text/javascript">
			function r(inV){
				if (inV== "M-" ||
					inV== "MC" ||
					inV== "MR" ||
					inV== "MS" ||
					inV== "M+" ||
					inV== "sqrt"
				){
					func(inV);
				}else if (inV== 1 ||
					inV== 2 ||
					inV== 3 ||
					inV== 4 ||
					inV== 5 ||
					inV== 6 ||
					inV== 7 ||
					inV== 8 ||
					inV== 9 ||
					inV== 0
				){
					numInput(inV);
				}else if (
					inV== "+" ||
					inV== "-" ||
					inV== "*" ||
					inV== "/"
				){
					opt(inV);
				}else if (inV== "EXP"){
					exp();
				}else if (inV== "."){
					if (entered){
						value = 0;
						digits = 1;
					}
					entered = false;
					if ((decimal == 0)&&(value == 0)&&(digits==0)){
						digits = 1;
					}
					if (decimal == 0){
						decimal = 1;
					}
					refresh();
				}else if (inV== "+/-"){
					if (exponent)
						expval = -expval;
					else
						value = -value;
					refresh();
				}else if (inV== "C"){
					level = 0;
					exponent = false;
					value = 0;
					enter();
					refresh();
				}else if (inV== "="){
					enter();
					while (level>0){
						evalx();
					}
					refresh();
				}
			}

			var totalDigits = 12;
			var pareSize = 12;

			var value = 0;
			var memory = 0;
			var level = 0;
			var entered = true;
			var decimal = 0;
			var fixed = 0;
			var exponent = false;
			var digits=0;
			var showValue = "0";
			var isShowValue = true;

			function stackItem(){
				this.value = 0;
				this.op = "";
			}

			function array(length){
				this[0] = 0;
				for (i=0; i<length; ++i){
					this[i] = 0;
					this[i] = new stackItem();
				}
				this.length = length;
			}

			stack = new array(pareSize);

			function push(value,op,prec){
				if (level==pareSize) return false;
				for (i=level;i>0; --i){
					stack[i].value = stack[i-1].value;
					stack[i].op = stack[i-1].op;
					stack[i].prec = stack[i-1].prec;
				}
				stack[0].value = value;
				stack[0].op = op;
				stack[0].prec = prec;
				++level;
				return true;
			}

			function pop(){
				if (level==0) return false;
				for (i=0;i<level; ++i)	{
					stack[i].value = stack[i+1].value;
					stack[i].op = stack[i+1].op;
					stack[i].prec = stack[i+1].prec;
				}
				--level;
				return true;
			}

			function format(value){
				var valStr = "" + value;
				if (valStr.indexOf("N")>=0 || (value == 2*value && value == 1+value)) return "Error ";
				var i = valStr.indexOf("e")
				if (i>=0){
					var expStr = valStr.substring(i+1,valStr.length);
					if (i>11) i=11;  // max 11 digits
					valStr = valStr.substring(0,i);
					if (valStr.indexOf(".")<0) valStr += ".";
					valStr += " " + expStr;
				} else {
					var valNeg = false;
					if (value < 0){
						value = -value;
						valNeg = true;
					}
					var valInt = Math.floor(value);
					var valFrac = value - valInt;
					var prec = totalDigits - (""+valInt).length - 1;
					if (prec<0) prec=0;
					if (! entered && fixed>0) prec = fixed;
					var mult = " 1000000000000000000".substring(1,prec+2);
					var frac = Math.floor(valFrac * mult + 0.5);
					valInt = Math.floor(Math.floor(value * mult + .5) / mult);
					if (valNeg)
						valStr = "-" + valInt;
					else
						valStr = "" + valInt;
					var fracStr = "00000000000000"+frac;
					fracStr = fracStr.substring(fracStr.length-prec, fracStr.length);
					i = fracStr.length-1;
					if (entered || fixed==0){
						while (i>=0 && fracStr.charAt(i)=="0")
							--i;
						fracStr = fracStr.substring(0,i+1);
					}
					if (i>=0) valStr += "." + fracStr;
				}
				return valStr;
			}

			function refresh(){
				var display = format(value);
				if (exponent){
					if (expval<0)
						display += " " + expval;
					else
						display += " +" + expval;
				}
				if (display.indexOf(".")<0 && display != "Error "){
					if (entered || decimal>0)
						display += ".";
					else
						display += " ";
				}
				document.getElementById("calInfoOutPut").value = display;
			}

			function evalx(){
				if (level==0) return false;
				op = stack[0].op;
				sval = stack[0].value;
				if (op == "+")
					value = parseFloat(sval) + value;
				else if (op == "-")
					value = sval - value;
				else if (op == "*")
					value = sval * value;
				else if (op == "/")
					value = sval / value;
				else if (op == "pow")
					value = Math.pow(sval,value);
				else if (op == "apow")
					value = Math.pow(sval,1/value);
				pop();
				return true;
			}

			function opt(op){
				enter();
				if (op=="+" || op=="-")
					prec = 1;
				else if (op=="*" || op=="/")
					prec = 2;
				else if (op=="pow" || op=="apow")
					prec = 3;
				if (level>0 && prec <= stack[0].prec)
					evalx();
				if (!push(value,op,prec)) value = "NAN";
				refresh();
			}

			function enter(){
				if (exponent){
					value = value * Math.exp(expval * Math.LN10);
				}
				entered = true;
				exponent = false;
				decimal = 0;
				fixed = 0;
			}

			function numInput(n){
				if (entered){
					value = 0;
					digits = 0;
					entered = false;
				}
				if (n==0 && digits==0){
					refresh();
					return;
				}
				if (exponent){
					if (expval<0)
						n = -n;
					if (digits < 3){
						expval = expval * 10 + n;
						++digits;
						refresh();
					}
					return;
				}
				if (value<0) n = -n;
				if (digits < totalDigits-1){
					++digits;
					if (decimal>0){
						decimal = decimal * 10;
						value = value + (n/decimal);
						++fixed;
					}else{
						value = value * 10 + n;
					}
				}
				refresh();
			}

			function exp(){
				if (entered || exponent) return;
				exponent = true;
				expval = 0;
				digits = 0;
				decimal = 0;
				refresh();
			}

			function func(f){
				enter();
				if (f=="MR") value = memory;
				else if (f=="M+"){
					memory += value;
				} else if (f=="MS") {
					memory = value;
				} else if (f=="MC") {
					memory = 0;
				} else if (f=="M-") {
					memory -= value;
				} else {
					if (f=="sqrt") value = Math.sqrt(value);
				}
				refresh();
			}
			</script>

			<!-- Edit the following to change the look and feel of this calculator -->
			<style>
			#calinfoout{
				background-color:#EEEEEE;
				width:178px;
			}
			.calinfoinner{
				padding:5px;
				border-top:1px solid #262626;
				border-left:1px solid #262626;
				border-right:2px outset #262626;
				border-bottom:2px outset #262626;
			}
			#calinfoout input{
				width:29px;
				height:25px;
				margin:2px;
				background-color:#FFF;
				font-family:arial,helvetica,sans-serif;
				font-size: 12px;
				border:1px solid #262626;
			}
			#calinfoout #calInfoOutPut{
				width:160px;
				font-size:12px;
				padding:1px;
				cursor:text;
				text-align: right;
				background-color:#B8C6A3;
				color:#000000;
			}
			#calinfoout .calinfonm{
				color:#FFF;
				font-weight:bold;
				background-color:#262626;
			}
			#calinfoout .calinfoop{
				color:#262626;
				font-weight:bold;
				background-color:#cccccc;
			}
			#calinfoout .calinfoeq{
				color:#FF0000;
				font-weight:bold;
				background-color:#DCADB0;
			}
			</style>

			<table align="center" cellpadding="0">
				<form onsubmit="return false;">
					<tr>
						<td>
							<div id="calinfoout">
								<div class="calinfoinner">
									<input type="text" name="input" size="16" id="calInfoOutPut" onclick="this.focus()" maxlength="16" value="0" readonly>
									<hr>
									<input type="button" value="1" onclick="r(1)" class="calinfonm"><input type="button" value="2" onclick="r(2)" class="calinfonm"><input type="button" value="3" onclick="r(3)" class="calinfonm"><input type="button" value="+" onclick=r("+") class="calinfoop"><input type="button" value="MS" onclick=r("MS") class="calinfoop"><br />
									<input type="button" value="4" onclick="r(4)" class="calinfonm"><input type="button" value="5" onclick="r(5)" class="calinfonm"><input type="button" value="6" onclick="r(6)" class="calinfonm"><input type="button" value="-" onclick=r("-") class="calinfoop"><input type="button" value="M+" onclick=r("M+") class="calinfoop">
									<input type="button" value="7" onclick="r(7)" class="calinfonm"><input type="button" value="8" onclick="r(8)" class="calinfonm"><input type="button" value="9" onclick="r(9)" class="calinfonm"><input type="button" value="*" onclick=r("*") class="calinfoop"><input type="button" value="M-" onclick=r("M-") class="calinfoop">
									<input type="button" value="0" onclick="r(0)" class="calinfonm"><input type="button" value="." onclick=r(".") class="calinfonm"><input type="button" value="EXP" onclick=r("EXP") class="calinfoop"><input type="button" value="/" onclick=r("/") class="calinfoop"><input type="button" value="MR" onclick=r("MR") class="calinfoop"><br />
									<input type="button" value="+/-" onclick=r("+/-") class="calinfoop"><input type="button" value="sqrt" onclick=r("sqrt") class="calinfoop"><input type="button" value="C" onclick=r("C") class="calinfoeq"><input type="button" value="=" onclick=r("=") class="calinfoeq"><input type="button" value="MC" onclick=r("MC") class="calinfoop"><br />
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<!-- Please help by keeping the following link. -->
						<td align="right">by <a href="http://www.calculator.net">calculator.net</a></td>
					</tr>
				</form>
			</table>
		</div>';
    	$output[] = $after_widget;
    	return join($output, "\n");
    }

	function calc_control() {
		$class_name = 'calculatornet_math_calculator';
		$calc_title = 'Math Calculator';

	    $options = get_option($class_name);

		if (!is_array($options)) $options = array('title'=>$calc_title);

		if ($_POST[$class_name.'_submit']) {
			$options['title'] = strip_tags(stripslashes($_POST[$class_name.'_title']));
			update_option($class_name, $options);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		echo '<p>Title: <input style="width: 180px;" name="'.$class_name.'_title" type="text" value="'.$title.'" /></p>';
		echo '<input type="hidden" name="'.$class_name.'_submit" value="1" />';
	}

    function calc_shortcode($args, $content=null) {
        return calculatornet_math_calculator::calc_display(false, $args);
    }

    function calc_widget($args) {
        echo calculatornet_math_calculator::calc_display(true, $args);
    }
}

add_action('widgets_init', array('calculatornet_math_calculator', 'calc_init'));

?>