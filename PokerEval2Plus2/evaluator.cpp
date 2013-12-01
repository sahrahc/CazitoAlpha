
char* print_card( int *hand)
{
    int i, r;
    char suit;
    static char *rank = "23456789TJQKA";
    
        r = (*hand >> 8) & 0xF;
        if ( *hand & 0x8000 )
            suit = 'c';
        else if ( *hand & 0x4000 )
            suit = 'd';
        else if ( *hand & 0x2000 )
            suit = 'h';
        else
            suit = 's';

        // make images fit this format
        printf( "%c_%c ", rank[r], suit );
        hand++;
}

// Navigate the lookup table
int GetHandValue(int* pCards)
{
    int p = HR[53 + *pCards++];
    p = HR[p + *pCards++];
    p = HR[p + *pCards++];
    p = HR[p + *pCards++];
    p = HR[p + *pCards++];
    p = HR[p + *pCards++];
    return HR[p + *pCards++];
}

// Initialize the 2+2 evaluator by loading the HANDRANKS.DAT file and
// mapping it to our 32-million member HR array. Call this once and
// forget about it.
int InitTheEvaluator()
{
    memset(HR, 0, sizeof(HR));
    FILE * fin = fopen("HANDRANKS.DAT", "rb");
    // Load the HANDRANKS.DAT file data into the HR array
    size_t bytesread = fread(HR, sizeof(HR), 1, fin);
    fclose(fin);
}