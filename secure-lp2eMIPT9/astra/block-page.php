<?php
include('libraries/Astra_ip.php');
$astra_ip = new Astra_ip();
$client_ip = $astra_ip->get_ip_address();
$formCheck = false; 
if($_POST){
   $formCheck = true; 
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attention!</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>

        * {
            line-height: 1.2;
            margin: 0;
        }

        html {
            color: #888;
            display: table;
            font-family: sans-serif;
            height: 100%;
            text-align: center;
            width: 100%;
        }

        body {
            display: table-cell;
            vertical-align: middle;
            margin: 2em auto;
        }

        h1 {
            color: #555;
            font-size: 2em;
            font-weight: 400;
        }

        p {
            margin: 0 auto;
        }

        div.image {
            margin-bottom: 20px;
        }

        div.image img {
            width: 50%;
        }

#toast-container{
        position: fixed;
    z-index: 999999;
    pointer-events: none;
}

@media 
(-webkit-min-device-pixel-ratio: 2), 
(min-resolution: 192dpi) { 
    /* Retina-specific stuff here */
    #toast-container>div{
    width: 430px;
        padding:15px;
    }
}
        
#toast-container>div{
    background-image: linear-gradient(to right,#0074ff 0%,#0003f5 51%,#0974f5 100%);
    transition: .5s;
    background-size: 200% auto;
        position: relative;
    pointer-events: auto;
        margin-bottom: 6px;
    padding: 10px;
    
    color: #fff;
  
    border-radius: 30px;
    width: 474px;
}

.toast-bottom-right{
        right: 12px;
    bottom: 12px;
}
      
        input#foo{
                padding: 7px;
                float: left;
                border: none;
                width: 75%;
                border-radius:13px;
                margin-left: 8px;
        }
        .btn{
           font-weight: bold;
            border: none;
            padding: 7px 8px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            border-radius: 13px;

        }

        .notes-list{
            text-align: left;
            list-style: none;
            clear: both;
            padding: 0;
            text-align: center;

        }

        .owner-check ul{
            padding: 0px;
        }

        .green{
                background-color: #14b800;
                    width: 48%;
                        border-radius: 15px;
    margin-left: 2px;
    padding: 5px;
        }

        .blue{
/*            background-color: #0c5292;*/
            background-color: #14b800;
          
                width: 25%;
    padding: 2px;

        }
        .button{
              
                border: none;
                color: white;
                
                text-align: center;
                text-decoration: none;
                display: inline-block;
                font-size: 14px;
                cursor: pointer;
                border-radius: 9px;
                
                margin-left: 2px;
        }

        lable{
                text-align: left;
                    width: 100%;

                margin-bottom: 10px;
                clear: both;
                float: left;
        }

        /*********************/
            .toast-title {
    font-weight: 700
}

.toast-message {
    -ms-word-wrap: break-word;
    word-wrap: break-word
}

.toast-message a,
.toast-message label {
    color: #fff;
        margin-right: 18px;

}

.toast-message a:hover {
    color: #ccc;
    text-decoration: none
}





.toast-top-full-width {
    top: 0;
    right: 0;
    width: 100%
}

.toast-bottom-full-width {
    bottom: 0;
    right: 0;
    width: 100%
}



.toast-bottom-right {
    right: 12px;
    bottom: 12px
}


#toast-container {
    position: fixed;
    z-index: 999999;
    pointer-events: none
}

#toast-container * {
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box
}

#toast-container>div {
    opacity: .8;
    -ms-filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=80);
    filter: alpha(opacity=80)
}

#toast-container>:hover {
    -moz-box-shadow: 0 0 12px #000;
    -webkit-box-shadow: 0 0 12px #000;
    box-shadow: 0 0 12px #000;
    opacity: 1;
    -ms-filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=100);
    filter: alpha(opacity=100);
    cursor: pointer
}



#toast-container.toast-bottom-center>div,
#toast-container.toast-top-center>div {
    width: 300px;
    margin-left: auto;
    margin-right: auto
}

#toast-container.toast-bottom-full-width>div,
#toast-container.toast-top-full-width>div {
    width: 96%;
    margin-left: auto;
    margin-right: auto
}


.toast-progress {
    position: absolute;
    left: 0;
    bottom: 0;
    height: 4px;
    background-color: #000;
    opacity: .4;
    -ms-filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=40);
    filter: alpha(opacity=40)
}

@media all and (max-width:240px) {
    #toast-container>div {
        padding: 8px 8px 8px 50px;
        width: 11em
    }
    #toast-container .toast-close-button {
        right: -.2em;
        top: -.2em
    }
}

@media all and (min-width:241px) and (max-width:480px) {
    #toast-container>div {
        padding: 8px 8px 8px 50px;
        width: 18em
    }
    #toast-container .toast-close-button {
        right: -.2em;
        top: -.2em
    }
}

@media all and (min-width:481px) and (max-width:768px) {
    #toast-container>div {
        padding: 15px 15px 15px 50px;
        width: 25em
    }
}

        /*********************/


        @media (min-width: 768px) {
            div.image img {
                width: 15%;
            }
        }

        div.go-back {
            padding-top: 20px;
        }

        div.go-back a {
            font-size: 14px;
        }

        p {
            width: 600px;
            margin-top: 30px;
        }

        a {
            cursor: pointer;
            color: #2b64d0;
        }

        @media only screen and (max-width: 280px) {

            body, p {
                width: 95%;
            }

            h1 {
                font-size: 1.5em;
                margin: 0 0 0.3em;
            }

        }

        .call_support {
            text-decoration: underline;
        }

    </style>

    <?php

    function hide_email($email)
    {
        $character_set = '+-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';
        $key = str_shuffle($character_set);
        $cipher_text = '';
        $id = 'e' . rand(1, 999999999);
        for ($i = 0; $i < strlen($email); $i += 1) $cipher_text .= $key[strpos($character_set, $email[$i])];
        $script = 'var a="' . $key . '";var b=a.split("").sort().join("");var c="' . $cipher_text . '";var d="";';
        $script .= 'for(var e=0;e<c.length;e++)d+=b.charAt(a.indexOf(c.charAt(e)));';
        $script .= 'document.getElementById("' . $id . '").innerHTML="<a href=\\"mailto:"+d+"\\">"+d+"</a>"';
        $script = "eval(\"" . str_replace(array("\\", '"'), array("\\\\", '\"'), $script) . "\")";
        $script = '<script type="text/javascript">/*<![CDATA[*/' . $script . '/*]]>*/</script>';
        return '<span id="' . $id . '">[javascript protected email address]</span>' . $script;
    }

    $support_email = hide_email('help@getastra.com');
    ?>
</head>
<body>

<div class='image'>
    <img alt='What ASTRA Means' src='https://www.getastra.com/assets/images/hotlink-ok/astra-logo-landing-pages.png'>
</div>
<h1>Sorry, this is not allowed.</h1>
<p>Thank you for visiting our website, unfortunately our website protection system has detected an issue with your IP
    address and wont let you proceed any further.</p>
<p>If you feel this is an error, please submit a <a class="call_support" onclick="FreshWidget.show(); return false;">support request</a>. Thank you for your patience.</p>
<p>
    <small><a href='https://www.getastra.com/'>https://www.getastra.com/</a></small>
</p>

<div class='go-back'>
    <p><a href='/'>Go to Homepage</a></p>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script type="text/javascript" src="https://s3.amazonaws.com/assets.freshdesk.com/widget/freshwidget.js"></script>
<script type="text/javascript">
    var ip = '<?php echo $client_ip; ?>';
    var attack_id = '<?php if(!empty($attack_param)) echo implode(",",$attack_param); else ""; ?>';
    var attack_param = null;
    if(attack_id != "" && attack_id != null)
        attack_param = "["+attack_id+"]"
    else
        attack_param = "";
    FreshWidget.init("", {
        "queryString": "&formTitle=Request block review&submitTitle=Submit&submitThanks=We+have+received+your+request.+Our+engineers+will+get+back+to+you+via+email+shortly.&widgetType=popup&captcha=yes&helpdesk_ticket[subject]=Requesting block review [" + (window.location.hostname) + "][" + ip + "]"+attack_param+"&searchArea=no&disable[custom_field][product_id]=true&helpdesk_ticket[ticket_body_attributes]=<div>test</div>",
        "utf8": "✓",
        "widgetType": "popup",
        "buttonType": "text",
        "buttonText": "Support",
        "buttonColor": "white",
        "buttonBg": "rgb(12, 82, 146)",
        "alignment": "4",
        "offset": "235px",
        "formHeight": "500px",
        "captcha": "yes",
        "url": "https://astrawebsecurity.freshdesk.com"
    });

    var clipboard = new ClipboardJS('.btn');

        

function reloadPage(){
    window.location.href = window.location.href; 
}




toastr.options = {
   "closeButton": false,
  "debug": false,
  "newestOnTop": false,
  "progressBar": false,
  "positionClass": "toast-bottom-right",
  "preventDuplicates": true,
  "onclick": null,
  "showDuration": "300",
  "hideDuration": "1000",
  "timeOut": 0,
  "extendedTimeOut": 0,
  "showEasing": "linear",
  "hideEasing": "linear",
  "showMethod": "fadeIn",
  "hideMethod": "fadeOut",
  "tapToDismiss": false
}


var x = toastr["success"]("Are you the owner of the website?");
var x1 = false;
$(x).click(function(){ 


    if($('#toast-container>div').length == 5 || $('body').find('div#toast-container div:first-child').attr('disable') == 'true'){
        return false;
    }else{
        $('body').find('div#toast-container div:first-child').attr('disable',true)
        setTimeout(function(){ toastr["success"]("Great! Lets' get you out of here! Follow steps below"); }, 500); 
    setTimeout(function(){ toastr["success"](" <input id='foo' value='<?php echo $client_ip; ?>'><button class='btn' data-clipboard-target='#foo'>Copy your IP</button>").attr('style', 'background: #0F2027;  background: -webkit-linear-gradient(to right, #2C5364, #203A43, #0F2027); background: linear-gradient(to right, #2C5364, #203A43, #0F2027); !important'); }, 2000);
    setTimeout(function(){ toastr["success"]("<ul class='notes-list'><li><label>Whitelist your IP/parameter</label><a target='_blank' href='https://www.getastra.com/kb/knowledgebase/whitelisting-ip-on-website-with-astra/' class='button blue'> Learn how </a></li></ul>").attr('style', 'background: #0F2027;  background: -webkit-linear-gradient(to right, #0F2027, #203A43, #2C5364); background: linear-gradient(to right, #0F2027, #203A43, #2C5364); !important'); }, 4000);
    setTimeout(function(){ toastr["success"]("<ul class='notes-list'><li><button onclick='reloadPage()' class='button green'> Refresh </button> </li></ul>").attr('style', 'background: #0F2027;  background: -webkit-linear-gradient(to right, #2C5364, #203A43, #0F2027); background: linear-gradient(to right, #2C5364, #203A43, #0F2027); !important'); }, 6000);
    }
   

 });



 //   toastr.success("Great! Lets' get you out of here! Follow steps below")
</script>
</body>
</html>