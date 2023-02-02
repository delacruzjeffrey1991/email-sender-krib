@extends('backend.layouts.master')

@section('title')
    @include('backend.pages.blogs.partials.title')
@endsection


@section('admin-content')
    @include('backend.pages.blogs.partials.header-breadcrumbs')
    <div class="container-fluid">
        @include('backend.layouts.partials.messages')
        <div class="table-responsive product-table">
            <button class="btn btn-success" data-toggle="modal" data-target="#addEmail">Add</button>

            <table class="table table-striped table-bordered display ajax_view">
                <thead>
                    <tr>
                        <th>DisplayName</th>
                        <th>TopicName</th>
                        <th width="100">Action</th>
                    </tr>
                </thead>
                 <tbody id="tb-body">
                    <tr id="tb-body-loader">
                        <td colspan="3">
                            <p class="text-center">Please wait... <i class="fa fa-spinner fa-spin"></i></p>
                        </td>
                    </tr>
                 </tbody>
            </table>
        </div>
    </div>

    <div id="viewList" class="modal fade" role="dialog"  data-backdrop="static" data-keyboard="false" >
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
      </div>
      <div class="modal-body">
             <table class="table table-striped table-bordered display ajax_view">
                <thead>
                    <tr>
                        <th>Contacts</th>
                    </tr>
                </thead>
                 <tbody id="tb-contacts">
                
                 </tbody>
            </table>
      </div>
       <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


@endsection
    

@section('scripts')
<script>
    $(document).ready(function() {
      fetch("{{url('api/contact-list?contact_list_name=jeffrey_contact_list')}}")
      .then(response => response.json())
      .then(result => {
        if(result.success) {
            const {  data } = result
            $("#tb-body-loader").hide()
            data.Topics.map((item) => {
                $("#tb-body").append(`<tr>    
                  <td>${item.DisplayName}</td>
                  <td>${item.TopicName}</td>
                  <td>
                    <button class="btn btn-success viewContacts" data-topic="${item.TopicName}" data-toggle="modal" data-target="#viewList"><i class="fa fa-search"></i></button>
                  </td>
                </tr>`)
            })
        }
      })
      .catch(error => {
        alert(JSON.stringify(error))
      });



    $(document).on("click", ".viewContacts", function() {
            const topic = $(this).attr("data-topic")
            $("#tb-contacts").html(`<tr id="tb-contacts-loader">
                        <td >
                            <p class="text-center">Please wait... <i class="fa fa-spinner fa-spin"></i></p>
                        </td>
                    </tr>`)

          fetch(`{{url('api/contact/list?contact_list_name=jeffrey_contact_list')}}&topic_name=${topic}`)
          .then(response => response.json())
          .then(result => {
            if(result.success) {
                const {  data } = result
                $("#tb-contacts-loader").hide()
                if(!data.length) {
                    $("#tb-contacts").append(`<tr>    
                      <td> <p class="text-center"> No contact found. </p> </td>
                    </tr>`)
                } else {
                    data.map((item) => {
                        $("#tb-contacts").append(`<tr>    
                          <td>${item}</td>
                        </tr>`)
                    })
                }
            }
          })
          .catch(error => {
            alert(JSON.stringify(error))
          });


    })

    })
</script>
@endsection
