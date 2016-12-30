from bs4 import BeautifulSoup
import os, re, csv, sys, math
import urllib2
import requests, collections

import networkx as nx


def createURLDict():
    # maps URLs to filenames
    urldict = {}
    # maps filenames to URLs
    filenames = {}
    with open('/home/ashwin/IR Assignment 3 HTML pages/mapABCNewsFile.csv', 'rb') as f:
        reader = csv.reader(f);
        for row in reader:
            urldict[row[1]] = row[0]
            filenames[row[0]] = row[1]

    with open('/home/ashwin/IR Assignment 3 HTML pages/mapFoxNewsFile.csv', 'rb') as f:
        reader2 = csv.reader(f);
        for row in reader2:
            urldict[row[1]] = row[0]
            filenames[row[0]] = row[1]

    return (urldict, filenames)

urldict, filenames = createURLDict()
links = []
total = len(urldict.items())
graph = collections.defaultdict(list)
print("Total files: %i" % total)
i = 0
j = 0
pattern = re.compile(r'<a href="(http[s]?://(abcnews.go.com|www.foxnews.com).+?)"')
for src_url, filename in urldict.items():
    with open(os.path.join("IR Assignment 3 HTML pages", filename), "r") as file:
        text = " ".join(map(lambda s: s.strip(), file.readlines()))
        #soup = BeautifulSoup(text)

        #for link in soup.findAll('a', attrs={'href': re.compile("^http://(abcnews.go.com|www.foxnews.com)")}):
        for url in pattern.findall(text):
            url = url[0]
            #print(url)
            #url = link.get('href')
            if url in urldict:
                graph[src_url].append(url)
                j += 1
        #sys.stdout.write('\n') 
        #sys.stdout.flush()
    if i % 100 == 0:
        sys.stdout.write("\rProcessing %.6f%% %i" % (i*100./total, i))
        sys.stdout.flush()

    i += 1
    

print("")
print("Total URLs pages: %i" % j)

g = nx.DiGraph(graph)
print len(g.nodes())
print len(g.edges())

# creates dict with URL mapped to page rank value
prDict = nx.pagerank(g, alpha=0.85,personalization=None,max_iter=30,tol=1e-06,nstart=None,weight='weight',dangling=None)

# save PR by using filenames instead of URLs as index
with open("external_pageRankFile.txt", "w") as pr_out:
    for url, pr in prDict.items():
        pr_out.write("%s=%f\n" % (urldict[url], pr))