@extends('layouts.guest')
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header p-2 ">
                        <div class=" d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18" id="pass_no">Update Permission </h4>

                            <div class="page-title-right">
                                <a class="btn btn-soft-info btn-sm waves-effect waves-light"
                                   href="{{ route('users.index') }}"><i
                                        class="fas fa-arrow-left"></i> User List</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <form action="{{ route('set_user_permission', ['user_id' => $user->id]) }}" method="POST"
                              id="updateForm">
                            @csrf
                            <div class="row mb-3 mt-3" style="margin-left: 10px;margin-right: 10px;">
                                <div class="col-md-4">
                                    <label for="role_name">User:</label> <span
                                        class="text-danger">*</span>
                                    <input type="text" name="role_name" id="role_name" class="form-control"
                                           value="{{$user->name}}">
                                    <span class="text-danger error-text role_name_error"></span>
                                </div>
                            </div>
                            <hr class="mb-3">
                            <div style="overflow: scroll;height: 400px;" >
                            @foreach($permissions as $permission => $value)
                                <div class="row mb-3" style="margin-left: 10px;margin-right: 10px;">
                                    <div class="col-lg-12">
                                        <h6 style="width:280px; height: 40px;padding: 10px; border-radius: 0px 20px 0px 0px;line-height: 25px;"
                                            class="fw-semibold bg-light-success border-bottom border-primary bg-primary text-white mb-0">
                                            <span class=""><input type="checkbox"
                                                                  class="form-check-input {{$permission}}"
                                                                  onclick="checkAllPermissions('{{Str::slug($permission)}}');"> </span>{{ mb_strtoupper($permission) }}
                                        </h6>
                                        <div class="card-body"
                                             style="padding: 8px;border-bottom: 1px solid #ddd;">
                                            <div class="row">
                                                @foreach($value as $k => $v)
                                                    <div class="col-3">
                                                        <div class="form-check">
                                                            <input type="checkbox" name="permission[]"
                                                                   class="form-check-input {{Str::slug($permission)}}"
                                                                   id="{{$v['id']}}"
                                                                   value="{{$v['name']}}" {{in_array($v['id'],$userPermissions)?'checked':''}}>
                                                            <label class="form-check-label"
                                                                   for="{{$v['id']}}">{{$v['name']}}</label>
                                                        </div>
                                                    </div>

                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            </div>
                            <div class="modal-footer" style="margin-right: 20px;margin-bottom: 20px;">
                                <button type="submit" class="btn btn-primary waves-effect waves-light"><i
                                        class="bx bx-save font-size-16 align-middle"></i> Give Permission
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div> <!-- container-fluid -->
@endsection
@section('script_section')
    <script>
        function checkAllPermissions(permission) {
            const checkboxes = document.querySelectorAll(`.${permission}`);
            const masterCheckbox = event.target;
            checkboxes.forEach((checkbox) => {
                checkbox.checked = masterCheckbox.checked;
            });
        }
    </script>
@endsection


