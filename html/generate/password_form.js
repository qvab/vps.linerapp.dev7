$(document).ready(function () {
  $("form#form-password").submit(function () {

      formData = $("form#form-password").serialize();

      $.ajax({
        //https://terminal.linerapp.com/licenseCheck
        url: "/licenseCheck.php",
        method: "GET",
        data: formData,
        success: function (data) {
          $("#inp-password").val(data);
        }

      });

      return false;
    }
  );

});