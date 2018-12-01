
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <script
            src="https://code.jquery.com/jquery-2.2.4.min.js"
            integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="
            crossorigin="anonymous"></script>


    <link href="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

</head>
<body>

<div id="update_notification" style="display:none;" class="alert alert-info">
    <button type="button" style="margin-left: 20px" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<script>
    $(document).ready(function() {
        $.ajax({
            type: 'GET',
            url: 'updater.check',
            async: false,
            success: function(response) {
                if(response != ''){
                    $('#update_notification').append('<strong>{{trans("laraupdater.Update_Available")}} <span class="badge badge-pill badge-info">v.'+response+'</span></strong><a role="button"  onclick="update()"  class="btn btn-sm btn-info pull-right update-btn">{{trans("laraupdater.Update_Now")}}</a>');
                    $('#update_notification').show();
                }
            }
        });

    });

    function update() {
        $(".update-btn").html('{{trans("laraupdater.Updating")}}');
        $.ajax({
            type: 'GET',
            url: 'updater.update',
            success: function(response) {

                if(response != ''){
                    $('#update_notification').append(response);
                    $(".update-btn").html('{{trans("laraupdater.Updated")}}');
                    $(".update-btn").attr("onclick","");
                }
            },
            error: function(response) {

                if(response != ''){
                    $('#update_notification').append(response);
                    $(".update-btn").html('{{trans("laraupdater.error_try_again")}}');

                }
            }
        });
    }
</script>
</body>
</html>
