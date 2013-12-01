/* 
 * File:   main.cpp
 * Author: Sahrah
 *
 * Created on July 8, 2012, 8:44 AM
 */

#include <cstdlib>
#include "arrays.h";
#include "mtrand.h";
#include "poker.h";
#include "pokerlib.h";
#include "stdafx.h";
#include "generate_table.h";
#include "evaluator.h"
/*
using namespace std;
 * 
 */
int main(int argc, char** argv) {

    init_deck(deck);
    InitTheEvaluator();
    
    switch(argv[1]) {
        case 'GetHandValue':
            // int GetHandValue(int* pCards)
            GetHandValue(argv[2]);
        case 'find_card':
            // find_card( int rank, int suit, int *deck )
            find_card(argv[2], argv[3]);
            break;
        case 'generate_table':
            // shouldn't be used much if ever
            generate_table();
            break;
            case ''
    }
    
    return 0;
}

