@extends('backend.layouts.master')

@section('title')
    @include('backend.pages.blogs.partials.title')
@endsection

@section('admin-content')
    <div class="container-fluid">
        <div class="create-page">
            <form 
            id="emailUpdate"
            method="POST" enctype="multipart/form-data" 
            data-parsley-validate data-parsley-focus="first">
                @csrf
                @method('put')
                <div class="form-body">

                     <input id="id-input" type="hidden" value="{{ $data->id }}"/>


                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label" for="title">Subject<span class="required">*</span></label>
                                    <input id="subject-input" type="text" class="form-control" id="title" name="subject" value="{{ $data->subject }}" placeholder="Enter Title" required=""/>
                                </div>
                            </div>
                        
                        <div class="row ">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="description">Message  <span class="optional">(optional)</span></label>
                                    <textarea id="message-input"  type="text" class="form-control tinymce_advance" id="description" name="message">{!!  $data->message !!}</textarea>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-actions">
                                    <div class="card-body">
                                        <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Save</button>
                                        <a href="{{ route('admin.blogs.index') }}" class="btn btn-dark">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
    $(".categories_select").select2({
        placeholder: "Select a Category"
    });

    $(document).ready(function() {
        $("#emailUpdate").submit(function( event ) {
          event.preventDefault();

          var urlencoded = new URLSearchParams();
            urlencoded.append("message", $('#message-input').val());
            urlencoded.append("subject", $('#subject-input').val());
            var id = $("#id-input").val()
            fetch(`{{url('api/update-email')}}/${id}`, {
            method: 'POST',
                body: urlencoded
            })
              .then(response => {
                window.location.href ="{{ route('admin.emailApprovals.index')}}" 
              })
              .then(result => console.log(result))
              .catch(error => console.log('error', error));
        });
    })
    </script>
@endsection