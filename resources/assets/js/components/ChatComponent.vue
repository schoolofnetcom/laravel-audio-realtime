<template>
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">Meu chat</div>

                    <div class="panel-body">
                        <div class="col-md-3">
                            <ul class="nav flex-column">
                                <li class="nav-item" v-for="user in users">
                                    <a class="nav-link" :class="{'btn-info': received.indexOf(user.id) > -1}" @click="loadMessages(user)">{{ user.name }}</a>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-9" v-if="!started">
                            <div class="panel panel-warning" v-if="!loading">
                                <div class="panel-heading">
                                    Falando com <strong>{{ currentChat.name }}</strong>
                                </div>
                            </div>

                            <div class="panel panel-info" v-if="messages.length === 0">
                                <div class="panel-heading">
                                    Nenhuma  mensagem para ouvir
                                </div>
                            </div>

                            <div class="panel" :class="{'panel-warning': currentChat.id == message.sender}" v-for="message in messages">
                                <div class="panel-body">
                                    <audio controls>
                                        <source :src="'/audios/' + message.audio">
                                    </audio>
                                </div>
                            </div>

                            <a id="rec" :class="{'recording': recording}" @click="rec()"></a>
                        </div>

                        <div class="col-md-9" v-if="started">
                            <div class="panel panel-success" v-if="!loading">
                                <div class="panel-heading">
                                    Escolha alguém para conversar...
                                </div>
                            </div>
                            <div class="panel panel-info" v-if="loading">
                                <div class="panel-heading">
                                    Obtendo conversa...
                                </div>
                            </div>
                            <div class="panel panel-danger" v-if="error">
                                <div class="panel-heading">
                                    Erro ao carregar a conversa...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                started: true,
                loading: false,
                error: false,
                currentChat: {},
                users: [],
                messages: [],
                recording: false,
                recorder: null,
                recordedData: [],
                received: []
            }
        },
        methods: {
            loadMessages(user) {
                this.loading = true;
                this.started = true;

                let index = this.received.indexOf(user.id);

                if (index > -1) {
                    this.received.splice(index, 1);
                }

                window.axios.get('/messages/' + user.id)
                    .then((response) => {
                        this.currentChat = user;
                        this.loading = false;
                        this.started = false;
                        this.messages = response.data;
                    })
                    .catch(() => {
                        this.loading = false;
                        this.error = true;
                    })
            },
            rec() {
                this.recording = !this.recording;

                if (this.recording) {
                    this.startRec();
                } else {
                    this.stopRec();
                }
            },
            startRec() {
                const config = {
                    audio: true,
                    video: false
                };

                const successCalback = (stream) => {
                    this.recorder = new MediaRecorder(stream);

                    this.recorder.ondataavailable = (e) => {
                        this.recordedData.push(e.data);
                        console.log(this.recordedData);
                    }

                    this.recorder.onstop = () => {
                        let blob = new Blob(this.recordedData, {type: 'video/webm'});

                        this.recorder = null;
                        this.recordedData = [];

                        let formData = new FormData();
                        formData.append('audio', blob);
                        formData.append('receiver', this.currentChat.id);

                        window.axios.post('/messages', formData).then((response) => {
                            console.log('done');
                            this.messages.splice(0, 0, response.data);
                        })
                    }

                    this.recorder.start();
                }

                const errorCalback = (err) => {
                    console.log(err);
                }


                navigator.getUserMedia(config, successCalback, errorCalback);
            },
            stopRec() {
                this.recorder.stop();
            }
        },
        mounted() {
            window.axios.get('/users').then((response) => {
                this.users = response.data;
            });

            Echo.channel('channel.messages.' + me)
                .listen('AudioSended', (e) => {
                    if (e.message.sender === this.currentChat.id) {
                        this.messages.splice(0, 0, e.message);
                    } else {
                        this.received.push(e.message.sender);
                    }
                });
        }
    }
</script>


<style>
@keyframes pulse {
    50% {
        background: transparent;
    }
}

a {
    cursor: pointer;
}

#rec {
    position: fixed;
    right: 10px;
    bottom: 10px;
}
#rec:after {
    display: block;
    content: '';
    width: 30px;
    height: 30px;
    background: red;
    border-radius: 30px;
}
#rec.recording:after {
    animation: pulse 1s infinite;
}
</style>
