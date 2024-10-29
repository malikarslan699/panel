<?php require "components/header.php"; ?>
<?php require "components/body.php"; ?>
<script>
    $(document).ready(function() {
        $("#loader").hide();
        $("#main_content").show();
    });
</script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" ></script>
<!-- Page Heading -->
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <link href="css/loader.css" rel="stylesheet">
  <div class="loading" id="loader"></div>
  <h1 class="h3 mb-0 text-gray-800">Create New VPN<span id="reseller" style="display: none;"><?php echo $_SESSION['username'] ?></span></h1>
</div>

<!-- Content Row -->
<form class="user col-sm-12 col-md-6 offset-md-3 col-lg-6" id="main_content" method="post">
<div class="form-group ">
  <div class="form-group row">
                  <div class="col-sm-12 mb-3 mb-sm-0 col-md-12 col-lg-12">
    <input type="text" class="form-control form-control-user" id="username" placeholder="Username (Like:Ali)" required>
  </div>
  </div>
 <div class="form-group row">
                  <div class="col-sm-12 mb-3 mb-sm-0 col-md-12 col-lg-12">
    <input type="text" class="form-control form-control-user" id="password" placeholder="Password" required>
  </div>
  </div>
  <div class="form-group row">
    <div class="col-sm-12 mb-3 mb-sm-0 col-md-12 col-lg-12">
        <?php if($_SESSION['status']!="Owner"){?>
    <select  class="browser-default custom-select custom-select-days " id="days" style="border-radius:10rem;height:50px;font-size:0.8rem;">
        <option value="31" selected>1 Month</option> 
        <option value="62">2 Months</option>
        <option value="93">3 Months</option>
        <option value="124">4 Months</option>
        <option value="155">5 Months</option>
        <option value="186">6 Months</option>
        <option value="365">1 Year</option>

    </select>
    <?php } else{
    ?>
        <input type="number" class="form-control form-control-user" id="days" placeholder="Days" required>
    <?php } ?>
    </div>
  </div>
</div>
    <div class="form-group d-none">Select Your Device:<br>
            <div class="custom-control custom-radio small px-5" style="display: inline" >
              <input type="radio" name="device" class="custom-control-input device" value="Android" id="Android" checked="checked">
              <label class="custom-control-label text-secondary" for="Android">Android</label>
            </div>
            <div class="custom-control custom-radio smallpx-5" style="display: inline">
              <input type="radio" name="device" class="custom-control-input device" value="IOS" id="IOS">
              <label class="custom-control-label text-secondary" for="IOS">iOS</label>
            </div>
           <div class="custom-control custom-radio small px-5" style="display: inline">
              <input type="radio" name="device" class="custom-control-input device" value="PC" id="PC">
              <label class="custom-control-label text-secondary" for="PC">PC</label>
            </div>
        </div>
      <button type="submit" class="btn btn-primary btn-user btn-block " id="btnAddVPN">
       Create PIN
      </button>
      <input type="reset" name="reset" id="rest" class="btn btn-secondary btn-user btn-block " value="Refresh">
      </div>
</form>
<script type="text/javascript">
  $(document).ready(function() {
      if($(document).width()<767)
      {
          $("body").toggleClass("sidebar-toggled");
          $(".sidebar").toggleClass("toggled");
      }
    $("#main_content").on("submit",function(event) {
      event.preventDefault();
      var username = $("#username").val();
      var password = $("#password").val();
      var days = $("#days").val();
      //console.log(days);
      var reseller = $("#reseller").text();
      var device="";
      $(".device").each(function() {
        if ($(this).is(":checked")) {  
          device=$(this).val();
        }
      });
      console.log(days);
      if (username=="" || /[^0-9a-zA-Z]/g.test(username)) {
        swal("Caution","Please Enter a Username.","info");
        return;
      }else if (password=="" || /[^0-9a-zA-Z]/g.test(password)) {
        swal("Caution","Please Enter a Password.","info");
        return;
      }else if (device=="") {
        swal("Caution","Please Select a Device.","info");
        return;
      }else{
         $("#loader").show();
        // $("#main_content").hide();
        $.ajax({
          url : "api/apiVPN.php",
          type : "post",
          data : {
            "user" : username,
            "password" : password,
            "device" : device,
            "days" : days,
            "reseller" : reseller 
          },
          
          success : function(response) {
              $("#loader").hide();
            console.log(response);
            var res = JSON.parse(response);
            if (res.state==1) {
              $("#rest").trigger("click");
              var server=$("#serverIPHome").text();
              var data = "Welcome To iTel VPN"+(device=="Android" ? "": "\nServer: <?php echo $core->getServer();  ?>")+"\n*Username:* "+username+"\n*Password:* "+password;
              swal("Success","Pin Created\nHere Are Your Credentials :\n"+data, "success",{
                   buttons : {
                       cancel:"Skip",
                       copy:{
                           text : "Copy",
                           value : "copy"
                       } 
                   }
               }).then((value)=>{
                   if(value=="copy")
                   {
                       Copier(data);
                   }
               });
            }else if (res.state == 0 || res.state == 2) {
              swal("ERROR","Username Already Exists", "error");
            }else if (res.state==3) {
              swal("Warning","Account Limit Reached.", "warning");
            }else{
              swal("ERROR","An Unknown Error Occured. Please Try Again Later", "error");
            }
          },
          error : function(error) {
            console.log(error);
          }
        });
      }
    });
      function Copier(str) {
       // Create new element
       var el = document.createElement('textarea');
       // Set value (string to be copied)
       el.value = str;
       // Set non-editable to avoid focus and move outside of view
       el.setAttribute('readonly', '');
       el.style = {position: 'absolute', left: '-9999px'};
       document.body.appendChild(el);
       // Select text inside element
       el.select();
       // Copy text to clipboard
       document.execCommand('copy');
       // Remove temporary element
       document.body.removeChild(el);
    }
  });
</script>
<?php require "components/footer.php"; ?>
