#include "my_socket.h"

#define SERVERHOST "localhost"

int main(int argc, char *argv[]) {
    void init_sockaddr(struct sockaddr_in *name, const char *hostname, uint16_t port);
    
    int sock;
    struct sockaddr_in servername;
    
    /* Create the socket. */
    sock = socket(PF_INET, SOCK_STREAM, 0);
    if (sock < 0) {
        perror("socket (client)");
        exit(EXIT_FAILURE);
    }
    
    /* Connect to the server. */
    init_sockaddr(&servername, SERVERHOST, PORT);
    if (0 > connect(sock, (struct sockaddr *) &servername, sizeof(servername))) {
        perror("connect (client)");
        exit(EXIT_FAILURE);
    }
    
    /* This is where things get interesting. */
    while (1) {
        read_from_socket(sock);
        printf("%s", response);
        
        // Get input from the user.
        fgets(message, MAXMSG, stdin);
        
        // Remove the newline character from the end of the string.
        message[strlen(message) - 1] = '\0';
        
        // Someone wants to escape :)
        if (strncmp(message, "bye", 3) == 0) {  
            printf("\t* Please be back soon...\n\n");
            break;
        }
        
        write_to_socket(sock);
    }
    
    close(sock);
    exit(EXIT_SUCCESS);
}

void init_sockaddr(struct sockaddr_in *name, const char *hostname, uint16_t port) {
    struct hostent *hostinfo;
    name->sin_family = AF_INET;
    name->sin_port = htons (port);
    
    hostinfo = gethostbyname(hostname);
    if (hostinfo == NULL) {
        fprintf(stderr, "Unknown host %s.\n", hostname);
        exit(EXIT_FAILURE);
    }
    
    name->sin_addr = *(struct in_addr *) hostinfo->h_addr;
}
