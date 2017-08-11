#include <mysql.h>

#include "my_socket.h"
#include "server.h"

char buffer[MAXMSG];
const char * const command_prompt = "   > ";
FILE *fp = NULL;

int member_id = 0;

int main(int argc, char *argv[]) {    
    int sock;
    struct sockaddr_in clientname;
    size_t size;
    
    /* Create the socket and set it up to accept connections. */
    sock = make_socket(PORT);
    if (listen(sock, 1) < 0) {
        perror("listen");
        exit(EXIT_FAILURE);
    }
    
    while (1) {
        int new;
        size = sizeof(clientname);
        new = accept(sock, (struct sockaddr *) &clientname, &size);
        
        if (new < 0) {
            perror("accept");
            exit(EXIT_FAILURE);
        }
        
        fprintf(stderr,
                "Server: connection from host %s, on port %hd.\n",
                inet_ntoa(clientname.sin_addr),
                ntohs(clientname.sin_port));
                
        /* Authenticate user. */
        char username[15];
        char password[15];
        
        // Get the username.
        sprintf(buffer, "%susername: ", command_prompt);
        strcpy(message, buffer);
        write_to_socket(new);
        if (read_from_socket(new) < 0) {
            close(new);
        }
        strcpy(username, response);
        
        // Get the user's password.
        sprintf(buffer, "%spassword: ", command_prompt);
        strcpy(message, buffer);
        write_to_socket(new);
        if (read_from_socket(new) < 0) {
            close(new);
        }
        strcpy(password, response);
        
        if (login(username, password) == 0) {
            write_to_socket(new);
            continue;
        }
        
        strcpy(message, command_prompt);
        while (1) {
            write_to_socket(new);
            if (read_from_socket(new) < 0) {
                close(new);
                break;
            }
            
            if (strlen(response) == 0) {
                // No command was sent (user just pressed `ENTER').
                strcpy(message, command_prompt);
            }
            else if (strcmp(response, "help") == 0) {
                char *help = get_help();
                strcpy(message, help);
            }
            else if (strcmp(response, "contribution check") == 0) {
                char *contribution = get_contribution();
                strcpy(message, contribution);
            }
            else if (strcmp(response, "benefits check") == 0) {
                char *benefits = get_benefits();
                strcpy(message, benefits);
            }
            else if (strcmp(response, "loan status") == 0) {
                char *loan_status = get_loan_status();
                strcpy(message, loan_status);
            }
            else if (strcmp(response, "loan repayment details") == 0) {
                char *loan_repayment_details = get_loan_repayment_details();
                strcpy(message, loan_repayment_details);
            }
            else if (starts_with(response, "contribution ")) {
                save_contribution(response);
            }
            else if (starts_with(response, "loan request")) {
                save_loan_request(response);
            }
            else if (starts_with(response, "idea")) {
                save_idea(response);
            }
            else {
                sprintf(buffer,
                        "\t- Error: Unknown command `%s'\n" \
                        "\t  Type help to see a list of supported commands\n" \
                        "\n%s", response, command_prompt);
                strcpy(message, buffer);
            }
        }
    }
}

int make_socket(uint16_t port) {
    int sock;
    struct sockaddr_in name;
    
    /* Create the socket. */
    sock = socket(PF_INET, SOCK_STREAM, 0);
    if (sock < 0) {
        perror("socket");
        exit(EXIT_FAILURE);
    }
    
    /* Give the socket a name. */
    name.sin_family = AF_INET;
    name.sin_port = htons(port);
    name.sin_addr.s_addr = htonl(INADDR_ANY);
    if (bind(sock, (struct sockaddr *) &name, sizeof(name)) < 0) {
        perror("bind");
        exit(EXIT_FAILURE);
    }
    
    return sock;
}

void save_idea(char *idea) {
    if (num_arguments(idea) < 4) {
        sprintf(buffer,
                "\t- Error: Too few parameters for idea.\n\n%s",
                command_prompt);
        strcpy(message, buffer);
    }
    else {
        fp = fopen("family_sacco.txt", "a");
        sprintf(buffer, "%s %d", idea, member_id);
        fprintf(fp, "%s\n", buffer);
        fclose(fp);
        
        sprintf(buffer,
                "\t+ Your idea has been successfully saved.\n\n%s",
                command_prompt);
        strcpy(message, buffer);
    }
}

void save_contribution(char *contribution) {
    if (num_arguments(response) < 4) {
        sprintf(buffer,
                "\t- Error: Too few parameters for contribution.\n\n%s",
                command_prompt);
        strcpy(message, buffer);
    }
    else {
        fp = fopen("family_sacco.txt", "a");
        sprintf(buffer, "%s %d", contribution, member_id);
        fprintf(fp, "%s\n", buffer);
        fclose(fp);
        
        sprintf(buffer,
                "\t+ Your contribution has been successfully saved.\n\n%s",
                command_prompt);
        strcpy(message, buffer);
    }
}

void save_loan_request(char *loan_request) {
    if (num_arguments(response) != 3) {
        sprintf(buffer,
                "\t- Error: Too few parameters for loan request.\n\n%s",
                command_prompt);
        strcpy(message, buffer);
    }
    else {
        fp = fopen("family_sacco.txt", "a");
        sprintf(buffer, "%s %d", loan_request, member_id);
        fprintf(fp, "%s\n", buffer);
        fclose(fp);
        
        sprintf(buffer,
                "\t+ Your loan request has been successfully submitted.\n\n%s",
                command_prompt);
        strcpy(message, buffer);
    }
}

char *get_contribution() {
    MYSQL *con = mysql_init(NULL);
    if (con == NULL) {
        fprintf(stderr, "mysql_init() failed.\n");
        exit(EXIT_FAILURE);
    }
    
    if (mysql_real_connect(con, "localhost", "root", "", "sacco", 0, NULL, 0) == NULL) {
        finish_with_error(con);
    }
    
    char query[MAXMSG];
    sprintf(query,
            "SELECT SUM(amount) FROM contributions WHERE member_id = %d",
            member_id);
    if (mysql_query(con, query)) {
        finish_with_error(con);
    }
    
    MYSQL_RES *result = mysql_store_result(con);
    if (result == NULL) {
        finish_with_error(con);
    }    
    
    MYSQL_ROW row = mysql_fetch_row(result);
    int contributions = 0;
    if (strcmp("NULL", row[0]) == 0) {
        sprintf(buffer, "\t- You haven't made any contributions yet.\n\n%s", command_prompt);
    }
    else {
        contributions = atoi(row[0]);
        int loan_payments = get_loan_payments();
        contributions = contributions - loan_payments;
        sprintf(buffer, "\t+ Your total contributions is: %d\n\n%s", contributions, command_prompt);
    }
    
    mysql_free_result(result);
    mysql_close(con);
    
    return buffer;
}

char *get_benefits() {
    MYSQL *con = mysql_init(NULL);
    if (con == NULL) {
        fprintf(stderr, "mysql_init() failed.\n");
        exit(EXIT_FAILURE);
    }
    
    if (mysql_real_connect(con, "localhost", "root", "", "sacco", 0, NULL, 0) == NULL) {
        finish_with_error(con);
    }
    
    char query[MAXMSG];
    sprintf(query,
            "SELECT SUM(amount) FROM benefits WHERE member_id = %d",
            member_id);
    if (mysql_query(con, query)) {
        finish_with_error(con);
    }
    
    MYSQL_RES *result = mysql_store_result(con);
    if (result == NULL) {
        finish_with_error(con);
    }
    
    MYSQL_ROW row = mysql_fetch_row(result);
    int benefits = 0;
    if (row[0]) {
        benefits = atoi(row[0]);
    }
    sprintf(buffer, "\t- Your total benefits is: UGX %d\n\n%s", benefits, command_prompt);
    
    mysql_free_result(result);    
    mysql_close(con);
      
    return buffer;
}

char *get_loan_status() {
    MYSQL *con = mysql_init(NULL);
    if (con == NULL) {
        fprintf(stderr, "mysql_init() failed.\n");
        exit(EXIT_FAILURE);
    }
    
    if (mysql_real_connect(con, "localhost", "root", "", "sacco", 0, NULL, 0) == NULL) {
        finish_with_error(con);
    }
    
    char query[MAXMSG];
    sprintf(query,
            "SELECT status FROM loans WHERE member_id = %d ORDER BY date_entered DESC LIMIT 1",
            member_id);
    if (mysql_query(con, query)) {
        finish_with_error(con);
    }
    
    MYSQL_RES *result = mysql_store_result(con);
    if (result == NULL) {
        finish_with_error(con);
    }
    
    int num_rows = mysql_num_rows(result);
    if (num_rows == 0) {
        sprintf(buffer, "\t- You haven't made any loan request.\n\n%s", command_prompt);
    }
    else {
        MYSQL_ROW row = mysql_fetch_row(result);
        if (strcmp("approved", row[0]) == 0) {
            sprintf(buffer, "\t+ Your loan request has been approved.\n\n%s", command_prompt);
        }
        else {
            sprintf(buffer, "\t- Your loan request has been denied.\n\n%s", command_prompt);
        }
        
        mysql_free_result(result);
    }
    
    mysql_close(con);    
    return buffer;
}

char *get_loan_repayment_details() {
    MYSQL *con = mysql_init(NULL);
    if (con == NULL) {
        fprintf(stderr, "mysql_init() failed.\n");
        exit(EXIT_FAILURE);
    }
    
    if (mysql_real_connect(con, "localhost", "root", "", "sacco", 0, NULL, 0) == NULL) {
        finish_with_error(con);
    }
    
    char query[MAXMSG];
    sprintf(query,
            "SELECT balance, \
            PERIOD_DIFF(DATE_FORMAT(NOW(), '%%Y%%m'), DATE_FORMAT(date_entered, '%%Y%%m')) AS elapsed \
            FROM loans WHERE member_id = %d AND status = 'approved' \
            ORDER BY date_entered DESC LIMIT 1",
            member_id);
    if (mysql_query(con, query)) {
        finish_with_error(con);
    }
    
    MYSQL_RES *result = mysql_store_result(con);
    if (result == NULL) {
        finish_with_error(con);
    }
    
    int num_rows = mysql_num_rows(result);
    if (num_rows == 0) {
        sprintf(buffer, "\t- You don't have any pending loan request.\n\n%s", command_prompt);
    }
    else {
        MYSQL_ROW row = mysql_fetch_row(result);
        int loan_balance = atoi(row[0]);
        int elapsed_months = atoi(row[1]);
        int months_left = 12 - elapsed_months;
        sprintf(buffer,
                "\t+ Your loan balance is: UGX %d, payable in %d months.\n\n%s",
                loan_balance, months_left, command_prompt);
    }
    
    mysql_free_result(result);    
    mysql_close(con);
      
    return buffer;
}

/* Checks whether the string s1 starts with s2 */
int starts_with(char *s1, char *s2) {
    if (strncmp(s1, s2, strlen(s2)) == 0) {
        return 1;
    } else {
        return 0;
    }
}

/* Counts the number arguments supplied for a command. */
int num_arguments(const char *command) {

    // Make a copy so that the original command is not changed.
    char s[strlen(command)];
    strcpy(s, command);
    
    int count = 0;
    char *token = strtok(s, " ");
    while (token != NULL) {
        ++count;
        token = strtok(NULL, " ");
    }

    return count;
}

char *get_help() {
    sprintf(buffer,
            "\t+ The following commands are supported:\n" \
            "\t  1) bye\n" \
            "\t  2) help\n" \
            "\t  3) loan status\n" \
            "\t  4) benefits check\n" \
            "\t  5) contribution check\n" \
            "\t  6) loan request <amount>\n" \
            "\t  7) loan repayment details\n" \
            "\t  8) idea <idea name> <capital> <simple description>\n" \
            "\t  9) contribution <amount> <date> <receipt_number>\n" \
            "\n%s", command_prompt);
    
    return buffer;
}

int login(char *username, char *password) {   
    MYSQL *con = mysql_init(NULL);
    if (con == NULL) {
        fprintf(stderr, "mysql_init() failed.\n");
        exit(EXIT_FAILURE);
    }
    
    if (mysql_real_connect(con, "localhost", "root", "", "sacco", 0, NULL, 0) == NULL) {
        finish_with_error(con);
    }
    
    char query[MAXMSG];
    sprintf(query,
            "SELECT id FROM members WHERE username = '%s' AND password = SHA1('%s')",
            username, password);
    if (mysql_query(con, query)) {
        finish_with_error(con);
    }
    
    MYSQL_RES *result = mysql_store_result(con);
    if (result == NULL) {
        finish_with_error(con);
    }
    
    int num_rows = mysql_num_rows(result);
    if (num_rows == 0) {
        sprintf(buffer, "\t- Invalid username/password combination\n\n%s", command_prompt);
        strcpy(message, buffer);
        mysql_close(con);
        
        return 0;
    }
    else {
        MYSQL_ROW row = mysql_fetch_row(result);
        member_id = atoi(row[0]);
        mysql_free_result(result);
        mysql_close(con);
        
        return 1;
    }
}

void finish_with_error(MYSQL *con) {
    fprintf(stderr, "%s\n", mysql_error(con));
    mysql_close(con);
    exit(EXIT_FAILURE);
}

int get_loan_payments() {
    MYSQL *con = mysql_init(NULL);
    if (con == NULL) {
        fprintf(stderr, "mysql_init() failed.\n");
        exit(EXIT_FAILURE);
    }
    
    if (mysql_real_connect(con, "localhost", "root", "", "sacco", 0, NULL, 0) == NULL) {
        finish_with_error(con);
    }
    
    char query[MAXMSG];
    sprintf(query,
            "SELECT SUM(amount) FROM loan_payments WHERE member_id = %d",
            member_id);
    if (mysql_query(con, query)) {
        finish_with_error(con);
    }
    
    MYSQL_RES *result = mysql_store_result(con);
    if (result == NULL) {
        finish_with_error(con);
    }
    
    MYSQL_ROW row = mysql_fetch_row(result);
    int loan_payments = 0;
    if (row[0]) {
        loan_payments = atoi(row[0]);
    }
    
    mysql_free_result(result);    
    mysql_close(con);
     
    return loan_payments;
}
