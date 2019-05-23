<?php $json = '{"leads":
{"add":[{"id":"12959105","name":"test distr 00010","status_id":"21833377","old_status_id":"","price":"0","responsible_user_id":"1427716","last_modified":"1548416974","modified_user_id":"1427716","created_user_id":"1427716","date_create":"1548416973","pipeline_id":"1369813","account_id":"21833371"}]},"account":{"subdomain":"linerappwidgets","id":"21833371","_links":{"self":"https://linerappwidgets.amocrm.ru"
}
}
}';

?>
<pre><?php var_dump(json_decode($json, true)); ?></pre>


<?php


$arData = [
  "leads" => [
    "add" => [
      "id" => "",
      "name" => "",
      "pipeline_id" => "",
      "account_id" => "",
    ]
  ],
  "account" => [
    "subdomain" => "linerappwidgets",
    "id" => "21833371",
    "_links" => [
      "self" => "https://linerappwidgets.amocrm.ru"
    ],
  ],
  "ajax" => 1
];
?>

<script>

  if (AMOCRM.data.is_card != "undefined" && AMOCRM.data.is_card) {
    if (AMOCRM.data.current_card.list_reload) {
      var myLeadField = false;
      $("span:contains('Моя сделка')").parent("div").next("div").find("input").change(function() {
        myLeadField = {
          name: "Моя сделка",
          values: 1
        };
      });

      if (myLeadField) {
        var myObject = {
          leads: {
            add: [
              {
                id: AMOCRM.data.current_card.id,
                account_id: AMOCRM.constant('account').id,
                custom_fields: [
                  myLeadField
                ]
              }
            ]
          },
          account: {
            subdomain: AMOCRM.constant('account').subdomain,
            id: AMOCRM.constant('account').id,
            _links: {
              self: "https://" + AMOCRM.constant('account').subdomain + ".amocrm.ru"
            }
          },
          subdomain: AMOCRM.constant('account').subdomain
        };

        $.ajax({
          type: "POST",
          data: $.param(myObject),
          url: "https://terminal.linerapp.com/leads/distribution",
          success: function(mes) {
            console.log(nes);
            location.replace();
          }
        });
      }
    }
  }

</script>
