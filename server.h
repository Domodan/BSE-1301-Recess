int make_socket(uint16_t port);

char *get_help();
int starts_with(char *s1, char *s2);
int num_arguments(const char *command);

void save_idea(char *idea);
void save_contribution(char *contribution);
void save_loan_request(char *loan_request);

char *get_benefits(void);
char *get_loan_status(void);
char *get_contribution(void);
char *get_loan_repayment_details(void);

int login(char *username, char *password);
void finish_with_error(MYSQL *);

int get_loan_payments();
