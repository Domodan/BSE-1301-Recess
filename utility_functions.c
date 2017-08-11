#include "my_socket.h"

extern char message[MAXMSG];
extern char response[MAXMSG];

int read_from_socket(int fd) {
    int nbytes;
    nbytes = read(fd, response, MAXMSG);
    if (nbytes < 0) {  /* Read error. */
        perror("read");
        exit(EXIT_FAILURE);
    }
    else if (nbytes == 0) {  /* End-of-file. */
        return -1;
    }
    else {  /* Data read. */
        return 0;
    }
}

void write_to_socket(int fd) {
    int nbytes;
    nbytes = write(fd, message, strlen(message) + 1);
    if (nbytes < 0) {
        perror("write");
        exit(EXIT_FAILURE);
    }
}
