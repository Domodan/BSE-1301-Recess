#ifndef _MY_SOCKET_H
#define _MY_SOCKET_H

#include <stdio.h>
#include <errno.h>
#include <netdb.h>
#include <unistd.h>
#include <string.h>
#include <stdlib.h>
#include <arpa/inet.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>

#define PORT 8000
#define MAXMSG 512

int read_from_socket(int fd);
void write_to_socket(int fd);

char message[MAXMSG];
char response[MAXMSG];

#endif
