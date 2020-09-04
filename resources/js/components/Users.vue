<template>
    <div class="container">
        <div class="row mt-5" v-if="$gate.isAdminOrAuthor()">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Users Table</h3>
                        <div class="card-tools">
                            <button class="btn btn-success" @click="newModal">Add New
                                <i class="fas fa-user-plus fa-fw"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Registered At</th>
                                    <th>Modify</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users.data" :key="user.id">
                                    <td>{{user.id}}</td>
                                    <td>{{user.name}}</td>
                                    <td>{{user.email}}</td>
                                    <td>{{user.type | upText}}</td>
                                    <td>{{user.created_at | myDate}}</td>
                                    <td>
                                        <a href="#" @click="editModal(user)">
                                            <i class="fa fa-edit blue"></i>
                                        </a> / 
                                        <a href="#" @click="deleteUser(user.id)">
                                            <i class="fa fa-trash red"></i>
                                        </a>
                                    </td>
                                </tr>
                                
                            </tbody>
                        </table>
                        <div class="card-footer">
                            <pagination :data="users" @pagination-change-page="getResults"></pagination>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
            </div>
        </div>
        <!-- Modal -->
        <div class="modal fade" id="addUser" tabindex="-1" role="dialog" aria-labelledby="addUserLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserLabel" v-if="editMode">Edit User</h5>
                    <h5 class="modal-title" id="addUserLabel" v-else>Add User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form @submit.prevent="editMode? editUser(): createUser()">
                
                    <div class="modal-body">
                        <div class="form-group">
                            <input type="text" class="form-control"
                                v-model="form.name" name="name" placeholder="Name"
                                :class="{'is-invalid':form.errors.has('name')}"
                            >
                            <has-error :form="form" field="name" />
                        </div>
                        <div class="form-group">
                            <input type="email" class="form-control"
                                v-model="form.email" name="email" placeholder="Email Address"
                                :class="{'is-invalid':form.errors.has('email')}"
                            >
                            <has-error :form="form" field="email" />
                        </div>
                        <div class="form-group">
                            <textarea class="form-control"
                                v-model="form.bio" name="bio" id="bio" placeholder="Short bio for User (optional)"
                                :class="{'is-invalid':form.errors.has('bio')}"
                            ></textarea>
                            <has-error :form="form" field="bio" />
                        </div>
                        <div class="form-group">
                            <select name="type" id="type"
                                v-model="form.type" class="form-control" 
                                :class="{'is-invalid': form.errors.has('type')}">
                                <option value="">   Select User Role</option>    
                                <option value="admin">Admin</option>    
                                <option value="user">Standard User</option>    
                                <option value="author">Author</option>    
                            </select>
                            <has-error :form="form" field="type" />
                        </div>
                        <div class="form-group">
                            <input type="password" class="form-control"
                                v-model="form.password" name="password" id="password"
                                :class="{'is-invalid':form.errors.has('password')}"
                            >
                            <has-error :form="form" field="password" />
                        </div>
                    
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" v-if="editMode">Update</button>
                        <button type="submit" class="btn btn-success" v-else>Create</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
        <div v-if="!$gate.isAdminOrAuthor()" class="mb-5">
            <not-found></not-found>
        </div>
    </div>
</template>

<script>
    export default {
        data(){
            return{
                editMode: false,
                users: {},
                form: new Form({
                    id: '',
                    name: '',
                    email: '',
                    password: '',
                    type: '',
                    bio: '',
                    photo: '',
                }),
            }
        },
        mounted() {
            this.loadUsers()
            Fire.$on('AfterCreate', () => {
                this.loadUsers()
            })
            Fire.$on('searching', () => {
                let query = this.$parent.search
                axios.get('api/findUser?q=' + query)
                    .then( (data) => {
                        this.users = data.data
                    })
                    .catch( () => {
                        
                    })
            })
        },
        methods: {
            /* pagination */
            getResults(page = 1) {
                let query = this.$parent.search
                axios.get('api/findUser?q='+query+'&'+'page='+page)
                    .then(response => {
                        this.users = response.data;
                    });
            },

            /*modales */
            newModal(){
                this.form.reset()
                this.editMode = false
                $('#addUser').modal('show')
                
            },
            editModal(user){
                this.form.reset()
                this.form.fill(user)
                this.editMode = true
                $('#addUser').modal('show')
            },
            /*actions*/
            editUser(){
                console.log("editando usuario")
                this.$Progress.start();
                this.form.put('api/user/'+this.form.id)
                    .then(()=>{
                        Fire.$emit('AfterCreate')
                        $('#addUser').modal('hide')
                        $('.modal-backdrop').remove();
                            swal.fire(
                                'Updated!',
                                'Information has been updated',
                                'success'
                            )
                        this.$Progress.finish();
                    })
                    .catch(()=>{
                        this.$Progress.fail();
                    })
            },
            createUser(){
                this.$Progress.start()
                this.form
                    .post('api/user')
                    .then(()=> {
                        Fire.$emit('AfterCreate')
                        $('#addUser').modal('hide')
                        $('.modal-backdrop').remove();
                        toast.fire({
                            icon: 'success',
                            type: 'success',
                            title: 'User Created in succesfully'
                        })
                        this.$Progress.finish()
                    })
                    .catch(()=> {
                        this.$Progress.fail();
                    })
            },
            deleteUser(id){
                swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                    if (result.value) {
                        this.form
                            .delete('api/user/'+id)    
                            .then(() => {
                                Fire.$emit('AfterCreate')
                                swal.fire(
                                    'Deleted!',
                                    'Your file has been deleted.',
                                    'success'
                                )
                            })
                            .catch( () => {
                                swal.fire('Failed!','There was something wrong','warning')
                            })
                    }
                })
            },
            loadUsers(){                
                if(this.$gate.isAdminOrAuthor()){
                    axios.get("api/user").then( ({data}) => {
                        this.users = data
                    })                
                }
            }
        }
    }
</script>

  