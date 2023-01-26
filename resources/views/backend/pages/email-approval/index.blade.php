@extends('backend.layouts.master')

@section('title')
    @include('backend.pages.blogs.partials.title')
@endsection


@section('admin-content')
    @include('backend.pages.blogs.partials.header-breadcrumbs')
    <div class="container-fluid">
        <button class="btn btn-success" data-toggle="modal" data-target="#addEmail">Add</button>
        @include('backend.layouts.partials.messages')
        <div class="table-responsive product-table">
            <table class="table table-striped table-bordered display ajax_view">
                <thead>
                    <tr>
                        <th>id</th>
                        <th>Contact List Name</th>
                        <th>Topic Name</th>
                        <!-- <th>Message</th> -->
                        <th>Subject</th>
                        <th>Status</th>
                        <th width="100">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($emails as $key => $item)
                        <tr>    
                          <td>{{$item->id}}</td>
                          <td>{{$item->contact_list_name}}</td>
                          <td>{{$item->topic_name}}</td>
                          <!-- <td>{{$item->message}}</td> -->
                          <td>{{$item->subject}}</td>
                          <td>{{$item->status}}</td> 
                          <td >
                            <button class="btn waves-effect waves-light btn-info btn-sm btn-circle approval-btn" data-id="{{$item->id}}" {{$item->status == 'approved' ? 'disabled' : ''}}><i class="fa fa-check"></i></button>
                            <a class="btn waves-effect waves-light btn-success btn-sm btn-circle ml-1" href="{{ route('admin.emailApprovals.edit', $item->id) }}"><i class="fa fa-edit"></i></a>
                         </td>              
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

<!-- Modal -->
<div id="loadingModal" class="modal fade" role="dialog"  data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
      </div>
      <div class="modal-body">
        <p class="text-center">Please wait... <i class="fa fa-spinner fa-spin"></i></p>
      </div>
    </div>
  </div>
</div>


<!-- Modal -->
<div id="addEmail" class="modal fade" role="dialog"  >
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
      </div>
      <div class="modal-body">
<form >
            
                <div class="form-body">
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="title">Contact List Name<span class="required">*</span></label>
                                    <input type="text" class="form-control"  id="create_contact_list_name" placeholder="Enter Title" required=""/>
                                </div>
                            </div>
                             <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="title">Topic Name<span class="required">*</span></label>
                                    <input type="text" class="form-control"  id="create_topic_name" placeholder="Enter Title" required=""/>
                                </div>
                            </div>
                             <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="title">Subject<span class="required">*</span></label>
                                    <input type="text" class="form-control"  id="create_subject" placeholder="Enter Title" required=""/>
                                </div>
                            </div>
                             <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="title">Message<span class="required">*</span></label>
                                   <textarea  class="form-control tinymce_advance"  id="create_message"></textarea>
                                </div>
                            </div>

                        </div>
                        </div>
                        </div>
                    </form>

      </div>
       <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-default" id="createEmail">Save</button>
      </div>
    </div>
  </div>
</div>
@endsection
    

@section('scripts')
<script>
    $(document).ready(function() {
        $("#createEmail").on("click", function() {
             var urlencoded = new URLSearchParams();
            urlencoded.append("contact_list_name", $("#create_contact_list_name").val());
            urlencoded.append("message", tinyMCE.get('create_message').getContent());
            urlencoded.append("topic_name", $("#create_topic_name").val());
            urlencoded.append("subject", $("#create_subject").val());

             fetch("{{url('api/save-email')}}", {
                method: 'POST',
                body: urlencoded
            })
              .then(response => response.json())
              .then(result => {
                window.location.reload()
              })
              .catch(error => {
                alert(JSON.stringify(error))
              });
        })

        $(document).on("click", '.approval-btn', function() {
            var id = $(this).attr("data-id")
            var emailList = @json($emails); 
            const selectedData = emailList.find((item) => item.id ==  id )

            var urlencoded = new URLSearchParams();
            urlencoded.append("contact_list_name", selectedData.contact_list_name);
            urlencoded.append("message", selectedData.message);
            urlencoded.append("topic_name", selectedData.topic_name);
            urlencoded.append("subject", selectedData.subject);
            $("#loadingModal").modal('show')
            fetch("{{url('api/send-email/topic')}}", {
                method: 'POST',
                body: urlencoded
            })
              .then(response => response.json())
              .then(result => {
                if(result?.success) {
                     var urlencodedStatus = new URLSearchParams();
                    urlencodedStatus.append("status", 'approved');
                    fetch(`{{url('api/update-email-status')}}/${id}`, {
                        method: 'POST',
                        body: urlencodedStatus
                    }).then(responseStatus => responseStatus.json())
                     .then(resultStatus => {
                        window.location.reload()
                     }) .catch(error => {
                        alert(JSON.stringify(error))
                        $("#loadingModal").modal('hide')
                     });
                } else {
                    alert("Error")
                    $("#loadingModal").modal('hide')
                }
              })
              .catch(error => {
                alert(JSON.stringify(error))
                $("#loadingModal").modal('hide')
              });
        })
    })
</script>
@endsection
