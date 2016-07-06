# -*- coding: utf-8 -*-
import networkx as nx
import numpy as np

__author__ = 'Asger Bachmann (agb@birc.au.dk)'


def fruchterman_reingold_layout(clusters, dist=4):
    pos = []
    for connections in clusters:
        g = nx.Graph()
        g.add_edges_from(connections)
        iterations = 50 if len(connections) > 15 else 20
        # Increase k from 1 / sqrt(nodes) to 2 / sqrt(nodes) to slightly increase the space between nodes.
        ps = nx.fruchterman_reingold_layout(g, iterations=iterations, k=dist / np.sqrt(len(g.node)))

        # The algorithm does not scale to [0, 1] as they claim but [-1, 1]. Rescale to [0, 1].
        xs = [p[0] for p in ps.values()]
        ys = [p[1] for p in ps.values()]
        min_x = min(xs)
        min_y = min(ys)
        max_x = max(xs)
        max_y = max(ys)
        max_min_x = max_x - min_x
        max_min_y = max_y - min_y

        for p in ps.values():
            p[0] = (p[0] - min_x) / max_min_x
            p[1] = (p[1] - min_y) / max_min_y

        pos.append(ps)
    return pos
