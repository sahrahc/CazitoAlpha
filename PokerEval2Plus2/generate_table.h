/* 
 * File:   generate_table.h
 * Author: Sahrah
 *
 * Created on July 8, 2012, 8:56 PM
 */

#ifndef GENERATE_TABLE_H
#define	GENERATE_TABLE_H

const char HandRanks[][16] = {"BAD!!","High Card","Pair","Two Pair","Three of a Kind","Straight","Flush","Full House","Four of a Kind","Straight Flush"};

__int64 IDs[612978];
int HR[32487834];   

int numIDs = 1;
int numcards = 0;
int maxHR = 0;
__int64 maxID = 0;

void generate_table();

#endif	/* GENERATE_TABLE_H */

